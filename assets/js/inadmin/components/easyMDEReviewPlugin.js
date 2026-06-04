window.Inachis.EasyMDEReviewPlugin = class {

    constructor(mde, options = {}) {
        this.mde = mde;
        this.cm = mde.codemirror;

        this.pageId = options.pageId;
        this.endpoint = options.endpoint;
		this.sidebarContainer = options.sidebarContainer || '#review-sidebar-container';

		this.activeThreadId = null;
		this.statusButton = null;

		this.markers = [];
		this.threads = [];
		this.reviewers = [];

        this.init();
    }

    async init() {
        this.injectStyles();
		this.createSidebar();
        this.createStatusBarButton();
        this.createCommentButton();

        this.attachSelectionListener();

		await this.loadReviewers();
		await this.loadThreads();
		this.updateStatusButton();
    }

    async loadThreads() {
        const response = await fetch(
            `${this.endpoint}/page/${this.pageId}`
        );

        this.threads = await response.json();
        this.renderThreads();
		this.updateStatusButton();
    }

	async loadReviewers() {
		const response =
			await fetch(
				`${this.endpoint}/reviewers`
			);

		this.reviewers =
			await response.json();
	}

    renderThreads() {
        this.clearMarkers();

        this.threads.forEach(thread => {

            if (thread.resolved) {
                return;
            }

            const marker = this.cm.markText(
                this.cm.posFromIndex(thread.currentStartOffset ?? thread.startOffset),
                this.cm.posFromIndex(thread.currentEndOffset ?? thread.endOffset),
                {
                    className:
						thread.id === this.activeThreadId
							? 'cm-review-highlight-active'
							: 'cm-review-highlight',
                    attributes: {
                        'data-thread-id': thread.id
                    }
                }
            );

            marker.reviewThread = thread;

            this.markers.push(marker);
        });

        // Allow CodeMirror to render the spans first
        setTimeout(() => {
            this.attachHighlightEvents();
        }, 0);
    }

    clearMarkers() {
        this.markers.forEach(marker => marker.clear());
        this.markers = [];
    }

    findThreadById(id) {
        return this.threads.find(
            thread => thread.id == id
        );
    }

    attachHighlightEvents() {
        document
            .querySelectorAll('[data-thread-id]')
            .forEach(el => {

                if (el.dataset.reviewBound) {
                    return;
                }

                el.dataset.reviewBound = '1';

                el.onclick = () => {

                    const thread =
                        this.findThreadById(
                            el.dataset.threadId
                        );

                    if (thread) {
                        this.openThread(thread);
                    }
                };
            });
    }

	createSidebar() {
		const sidebar =
			document.createElement('div');

		sidebar.className =
			'review-sidebar';

		sidebar.innerHTML = `
			<div class="review-sidebar-header">
				Reviews
			</div>

			<div class="review-sidebar-body">
				No review selected
			</div>
		`;

		const container =
			document.querySelector(
				this.sidebarContainer
			);

		if (container) {
			container.appendChild(sidebar);
		} else {
			document.body.appendChild(sidebar);
		}

		this.sidebar = sidebar;
	}

    createStatusBarButton() {
		const bar = this.mde.element
			.parentElement
			.querySelector('.editor-statusbar');

		if (!bar) {
			return;
		}

		const button =
			document.createElement('span');

		button.className =
			'review-status-button';

		button.onclick = () => {
			this.showThreadList();
		};

		bar.appendChild(button);

		this.statusButton = button;

		this.updateStatusButton();
	}

    createCommentButton() {

        const button = document.createElement('button');

        button.className = 'review-comment-button';
        button.textContent = '💬 Comment';

        button.style.display = 'none';

        document.body.appendChild(button);

        button.onclick = () => {
            this.openCommentDialog();
        };

        this.commentButton = button;
    }

    attachSelectionListener() {

        this.cm.on('cursorActivity', () => {

            const selection =
                this.cm.getSelection();

            if (!selection.trim()) {
                this.hideCommentButton();
                return;
            }

            this.showCommentButton();
        });
    }

	showThreadList() {
		const body =
			this.sidebar.querySelector(
				'.review-sidebar-body'
			);

		body.innerHTML = '';

		const openThreads =
			this.threads.filter(
				t => !t.resolved
			);

		if (!openThreads.length) {

			body.innerHTML =
				'<p>No open reviews</p>';

			return;
		}

		openThreads.forEach(thread => {

			const item =
				document.createElement('div');

			item.className =
				'review-thread-item';

			item.innerHTML = `
				<strong>
					${this.escapeHtml(
						thread.selectedText
					).substring(0, 60)}
				</strong>
				<br>
				${thread.comments?.length || 0}
				comment(s)
			`;

			item.onclick = () => {

				this.jumpToThread(thread);

				this.openThread(thread);
			};

			body.appendChild(item);
		});
	}

    showCommentButton() {

        const selection =
            window.getSelection();

        if (!selection.rangeCount) {
            return;
        }

        const rect =
            selection
                .getRangeAt(0)
                .getBoundingClientRect();

        this.commentButton.style.display = 'block';

        this.commentButton.style.position = 'fixed';

        this.commentButton.style.left =
            `${rect.right + 10}px`;

        this.commentButton.style.top =
            `${rect.top}px`;
    }

    hideCommentButton() {
        this.commentButton.style.display = 'none';
    }

    openCommentDialog() {

        const message = prompt(
            'Enter review comment'
        );

        if (!message) {
            return;
        }

        this.createThread(message);
    }

    async createThread(message) {

        const from =
            this.cm.indexFromPos(
                this.cm.getCursor('from')
            );

        const to =
            this.cm.indexFromPos(
                this.cm.getCursor('to')
            );

        const selectedText =
            this.cm.getSelection();

        const content =
            this.cm.getValue();

        const contextBefore =
            content.substring(
                Math.max(0, from - 50),
                from
            );

        const contextAfter =
            content.substring(
                to,
                Math.min(
                    content.length,
                    to + 50
                )
            );

        const response = await fetch(
            `${this.endpoint}/page/${this.pageId}`,
            {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify({
                    startOffset: from,
                    endOffset: to,
                    selectedText,
                    contextBefore,
                    contextAfter,
                    message
                })
            }
        );

        if (!response.ok) {
            console.error(
                'Failed to create review thread'
            );
            return;
        }

        const thread = await response.json();
        this.threads.push(thread);
        this.renderThreads();
		this.updateStatusButton();
		this.hideCommentButton();
    }

    openThread(thread) {
		this.activeThreadId = thread.id;

		const body = this.sidebar.querySelector(
			'.review-sidebar-body'
		);

		body.innerHTML = '';

		const title = document.createElement('div');

		title.className = 'review-thread-title';

		title.innerHTML = `
			<strong>Selected text</strong>
			<blockquote>
				${this.escapeHtml(thread.selectedText)}
			</blockquote>
		`;

		body.appendChild(title);
		body.appendChild(
			this.createAssignmentBox(thread)
		);

		if (thread.comments?.length) {

			thread.comments.forEach(comment => {

				const item =
					document.createElement('div');

				item.className =
					'review-comment';

				item.innerHTML = `
					<div class="review-comment-author">
						${this.escapeHtml(
							comment.author.name
						)}
					</div>

					<div class="review-comment-message">
						${this.escapeHtml(
							comment.message
						)}
					</div>
				`;

				body.appendChild(item);
			});
		}

		body.appendChild(
			this.createReplyForm(thread)
		);

		body.appendChild(
			this.createResolveButton(thread)
		);

		this.renderThreads();
	}

	async replyToThread(
		threadId,
		message
	) {

		const response = await fetch(
			`${this.endpoint}/thread/${threadId}/reply`,
			{
				method: 'POST',

				headers: {
					'Content-Type':
						'application/json'
				},

				body: JSON.stringify({
					message
				})
			}
		);

		if (!response.ok) {
			alert('Failed to add reply');
			return;
		}

		await this.loadThreads();
		this.updateStatusButton();
		const thread = this.findThreadById(threadId);

		if (thread) {
			this.openThread(thread);
		}
	}

	async resolveThread(threadId) {
		const response = await fetch(
			`${this.endpoint}/thread/${threadId}/resolve`,
			{
				method: 'POST'
			}
		);

		if (!response.ok) {
			alert(
				'Failed to resolve review'
			);
			return;
		}

		await this.loadThreads();
		this.updateStatusButton();

		const body =
			this.sidebar.querySelector(
				'.review-sidebar-body'
			);

		body.innerHTML = `
			<p>
				Review resolved.
			</p>
		`;
	}

	escapeHtml(text) {
		const div =
			document.createElement('div');

		div.textContent =
			text ?? '';

		return div.innerHTML;
	}

	jumpToThread(thread) {
		const from =
			this.cm.posFromIndex(
				thread.currentStartOffset ?? thread.startOffset
			);

		const to =
			this.cm.posFromIndex(
				thread.currentEndOffset ?? thread.endOffset
			);

		this.cm.focus();

		this.cm.setSelection(
			from,
			to
		);

		this.cm.scrollIntoView(
			from,
			150
		);
	}

    injectStyles() {

        const style =
            document.createElement('style');

        style.innerHTML = `
            .cm-review-highlight {
                background: rgba(255, 215, 0, 0.35);
                cursor: pointer;
            }
			.cm-review-highlight-active {
    			background:
        		rgba(255,140,0,.45);
    			cursor:pointer;
			}

            .review-status-button {
                cursor: pointer;
                margin-left: 10px;
            }

            .review-comment-button {
                z-index: 99999;
                border: none;
                border-radius: 4px;
                padding: 6px 10px;
                cursor: pointer;
            }
        `;

        document.head.appendChild(style);
    }

	updateStatusButton() {
		if (!this.statusButton) {
			return;
		}

		const openCount =
			this.threads.filter(
				thread => !thread.resolved
			).length;

		this.statusButton.textContent =
			`💬 Reviews (${openCount})`;
	}

	createReplyForm(thread) {
		const wrapper =
			document.createElement('div');

		wrapper.className =
			'review-reply-form';

		wrapper.innerHTML = `
			<textarea
				class="review-reply-text"
				placeholder="Reply to this review..."
				rows="4"></textarea>

			<button
				type="button"
				class="button button--add review-reply-button">
				Reply
			</button>
		`;

		wrapper
			.querySelector(
				'.review-reply-button'
			)
			.onclick = async () => {

				const textarea =
					wrapper.querySelector(
						'.review-reply-text'
					);

				const message =
					textarea.value.trim();

				if (!message) {
					return;
				}

				await this.replyToThread(
					thread.id,
					message
				);
			};

		return wrapper;
	}

	createAssignmentBox(thread) {
		const wrapper =
			document.createElement('div');

		wrapper.className =
			'review-assignment';

		const select =
			document.createElement('select');

		select.innerHTML =
			`<option value="">
				Unassigned
			</option>`;

		this.reviewers.forEach(user => {

			const option =
				document.createElement(
					'option'
				);

			option.value = user.id;

			option.textContent =
				user.name;

			if (
				thread.assignedTo &&
				thread.assignedTo.id === user.id
			) {
				option.selected = true;
			}

			select.appendChild(option);
		});

		select.onchange = () => {

			this.assignThread(
				thread.id,
				select.value
			);
		};

		wrapper.appendChild(select);

		return wrapper;
	}

	async assignThread(
		threadId,
		userId
	) {

		await fetch(
			`${this.endpoint}/thread/${threadId}/assign`,
			{
				method: 'POST',

				headers: {
					'Content-Type':
						'application/json'
				},

				body: JSON.stringify({
					userId
				})
			}
		);

		await this.loadThreads();

		const updated =
			this.findThreadById(
				threadId
			);

		if (updated) {
			this.openThread(updated);
		}
	}

	createResolveButton(thread) {
		const button =
			document.createElement('button');

		button.type = 'button';

		button.className =
			'button button--positive review-resolve-button';

		button.textContent =
			'Resolve';

		button.onclick = async () => {

			if (
				!confirm(
					'Resolve this review?'
				)
			) {
				return;
			}

			await this.resolveThread(
				thread.id
			);
		};

		return button;
	}
};
