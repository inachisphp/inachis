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
		this.showResolved = false;

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
		const response =await fetch(
			`${this.endpoint}/reviewers`
		);
		this.reviewers =await response.json();
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
        document.querySelectorAll('[data-thread-id]')
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
		const sidebar = document.createElement('div');
		sidebar.className = 'review-sidebar';
		sidebar.innerHTML = `
			<div class="review-sidebar-header">
				Reviews
			</div>

			<div class="review-sidebar-body">
				No review selected
			</div>
		`;

		const container = document.querySelector(
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
		const bar = this.mde.element.parentElement.querySelector('.editor-statusbar');
		if (!bar) {
			return;
		}

		const button = document.createElement('span');
		button.className = 'review-status-button';
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
        button.textContent = '💬';
        button.style.display = 'none';
        document.body.appendChild(button);

        button.onclick = () => {
            this.openCommentDialog();
        };

        this.commentButton = button;
    }

	createReopenButton(thread) {
		const button = document.createElement('button');

		button.type = 'button';

		button.className =
			'button review-reopen-button';

		button.textContent =
			'Reopen Review';

		button.onclick = async () => {

			await this.reopenThread(
				thread.id
			);
		};

		return button;
	}

    attachSelectionListener() {

        this.cm.on('cursorActivity', () => {
            const selection = this.cm.getSelection();

            if (!selection.trim()) {
                this.hideCommentButton();
                return;
            }

            this.showCommentButton();
        });
    }

	showThreadList() {
		const body = this.sidebar.querySelector(
			'.review-sidebar-body'
		);

		body.innerHTML = '';
		const toggle = document.createElement('a');
		toggle.href = '#';
		toggle.className = 'review-toggle-resolved';
		toggle.textContent = this.showResolved
				? 'Hide resolved reviews'
				: 'Show resolved reviews';
		toggle.onclick = event => {
			event.preventDefault();
			this.showResolved = !this.showResolved;
			this.showThreadList();
		};
		console.log(toggle);
		body.appendChild(toggle);

		const visibleThreads = this.threads.filter(thread => {
			if (!this.showResolved) {
				return !thread.resolved;
			}

			return true;
		});

		if (!visibleThreads.length) {
			body.innerHTML = '<p>No open reviews</p>';
			return;
		}

		visibleThreads.forEach(thread => {
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

				${thread.resolved
					? '<span class="review-status-resolved">Resolved</span>'
					: ''
				}

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

    showCommentButton(coords) {
        const selection = window.getSelection();
        if (!selection.rangeCount) {
            return;
        }

		const from = this.cm.getCursor('from');
		const to = this.cm.getCursor('to');
		const start = this.cm.cursorCoords(from, 'page');
		const end = this.cm.cursorCoords(to, 'page');
		const left = (start.left + end.left) / 2;
		const top = Math.min(start.top, end.top);

        this.commentButton.style.display = 'block';
		this.commentButton.style.position = 'absolute';
		this.commentButton.style.left = `${left}px`;
		this.commentButton.style.top = `${top - 40}px`;
    }

    hideCommentButton() {
        this.commentButton.style.display = 'none';
    }

    openCommentDialog() {
		const body =
			this.sidebar.querySelector(
				'.review-sidebar-body'
			);

		body.innerHTML = '';

		const wrapper = document.createElement('div');
		wrapper.className = 'review-new-thread';
		wrapper.innerHTML = `
			<h3>Create Review</h3>

			<div class="review-selection-preview">
				${this.escapeHtml(
					this.cm.getSelection()
				)}
			</div>

			<textarea
				class="review-new-thread-message"
				rows="5"
				placeholder="Enter review comment..."
			></textarea>

			<button
				type="button"
				class="button button--add">
				Create Review
			</button>
		`;

		wrapper.querySelector('button')
			.onclick = async () => {
				const message = wrapper
					.querySelector(
						'.review-new-thread-message'
					)
					.value
					.trim();

				if (!message) {
					return;
				}

				await this.createThread(
					message
				);
			};

		body.appendChild(wrapper);
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
		this.openThread(thread);
    }

    openThread(thread) {
		if (thread.needsRebase) {
			const warning = document.createElement('div');

			warning.className = 'review-warning';
			warning.innerHTML = `
				⚠ This review could not be
				automatically relocated after
				content changes.
			`;

			body.appendChild(warning);
		}
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

		if (!thread.resolved) {
			body.appendChild(this.createReplyForm(thread));
			body.appendChild(this.createResolveButton(thread));
		} else {
			body.appendChild(this.createReopenButton(thread));
		}
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

	async reopenThread(threadId) {
		const response = await fetch(
			`${this.endpoint}/thread/${threadId}/reopen`,
			{
				method: 'POST'
			}
		);

		if (!response.ok) {
			alert(
				'Failed to reopen review'
			);
			return;
		}

		await this.loadThreads();

		const thread =
			this.findThreadById(
				threadId
			);

		if (thread) {
			this.openThread(thread);
		}
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
			.review-selection-preview {
				padding: 10px;
				margin-bottom: 10px;
				border: 1px solid #ddd;
				background: #fafafa;
				font-size: 0.9em;
			}

			.review-new-thread-message {
				width: 100%;
				margin-bottom: 10px;
			}

			.review-resolve-confirmation {
				margin-top: 12px;
				padding: 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
			}

			.review-confirm-actions {
				display: flex;
				gap: 8px;
			}

			.review-confirm-message {
				margin-bottom: 8px;
			}

			.review-toggle-resolved {
				display:block;
				margin-bottom:10px;
			}

			.review-status-resolved {
				display:inline-block;
				margin-left:8px;
				font-size:.85em;
				opacity:.7;
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
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'button button--positive review-resolve-button';
		button.textContent = 'Resolve';
		button.onclick = () => {
			this.showResolveConfirmation(
				thread
			);
		};

		return button;
	}

	showResolveConfirmation(thread) {
		const body =
			this.sidebar.querySelector(
				'.review-sidebar-body'
			);

		const existing =
			body.querySelector(
				'.review-resolve-confirmation'
			);

		if (existing) {
			existing.remove();
			return;
		}

		const container =
			document.createElement('div');

		container.className =
			'review-resolve-confirmation';

		container.innerHTML = `
			<p>
				Resolve this review?
			</p>

			<button
				type="button"
				class="button button--positive">
				Resolve
			</button>

			<button
				type="button"
				class="button">
				Cancel
			</button>
		`;

		const buttons =
			container.querySelectorAll(
				'button'
			);

		buttons[0].onclick =
			async () => {

				await this.resolveThread(
					thread.id
				);
			};

		buttons[1].onclick =
			() => container.remove();

		body.appendChild(container);
	}
};
