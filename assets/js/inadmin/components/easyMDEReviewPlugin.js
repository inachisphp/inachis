window.Inachis.EasyMDEReviewPlugin = class {
    constructor(mde, options = {}) {
        this.mde = mde;
        this.cm = mde.codemirror;

        this.pageId = options.pageId;
        this.endpoint = options.endpoint;
		this.sidebarContainer = options.sidebarContainer || '#review-sidebar-container';
		this.sidebarView = 'list';

		this.activeThreadId = null;
		this.statusButton = null;

		this.markers = [];
		this.threads = [];
		this.reviewers = [];
		this.showResolved = false;

        this.init();
    }

    async init() {
		this.createSidebar();
        this.createStatusBarButton();
        this.createCommentButton();
		this.attachCodeMirrorListeners();
        this.attachSelectionListener();

		try {
            // Fetch configuration data concurrently
            await Promise.all([
                this.loadReviewers(),
                this.loadThreads()
            ]);
            
			this.updateStatusButton();
            this.showThreadList();
        } catch (error) {
            console.error('Failed to initialize Review Plugin:', error);
        }
    }

    async assignThread(threadId, userId) {
        try {
            const response = await fetch(`${this.endpoint}/thread/${threadId}/assign`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ userId })
            });
            if (!response.ok) throw new Error('Assignment failed');
            
            await this.loadThreads();
            const updated = this.findThreadById(threadId);
            if (updated) this.openThread(updated);
        } catch (error) {
            console.error(error);
        }
    }

	attachCodeMirrorListeners() {
        this.cm.getWrapperElement().addEventListener('click', (event) => {
            const highlight = event.target.closest('[data-thread-id]');
            if (!highlight) return;

            event.preventDefault();
            event.stopPropagation();

            const thread = this.findThreadById(highlight.dataset.threadId);
            if (thread) {
                if (this.activeThreadId !== thread.id) {
                    this.jumpToThread(thread);
                }
                this.openThread(thread);
            }
        });

        this.cm.on('change', () => {
            this.syncMarkerOffsets();
        });
    }

    attachSelectionListener() {
        // Debounce to prevent layout thrashing during active drag selections
        let timeout;
        this.cm.on('cursorActivity', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const selection = this.cm.getSelection();
                if (!selection || !selection.trim()) {
                    this.hideCommentButton();
                    return;
                }
                this.showCommentButton();
            }, 100);
        });
    }

    clearMarkers() {
        this.markers.forEach(marker => marker.clear());
        this.markers = [];
    }

    createAssignmentBox(thread) {
        const select = this.ui('select', {
            className: 'review-assignee-select',
            onchange: () => this.assignThread(thread.id, select.value)
        }, this.ui('option', { value: '' }, 'Unassigned'));

        this.reviewers.forEach(user => {
            const isSelected = thread.assignedTo && String(thread.assignedTo.id) === String(user.id);
            select.appendChild(
                this.ui('option', { 
                    value: user.id,
                    ...(isSelected ? { selected: 'selected' } : {})
                }, user.name)
            );
        });

        return this.ui('div', { className: 'review-assignment' }, select);
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
        button.className = 'button review-reopen-button';
        button.textContent = 'Reopen Review';
        button.onclick = async () => {
            await this.reopenThread(thread.id);
        };
        return button;
    }

    createReplyForm(thread) {
        const textarea = this.ui('textarea', {
            className: 'review-reply-text',
            placeholder: 'Reply to this review...',
            rows: '4'
        });

        const btn = this.ui('button', {
            type: 'button',
            className: 'button button--add review-reply-button',
            onclick: async () => {
                const message = textarea.value.trim();
                if (!message) return;
                btn.disabled = true;
                await this.replyToThread(thread.id, message);
            }
        }, 'Reply');

        return this.ui('div', { className: 'review-reply-form' }, textarea, btn);
    }

    createResolveButton(thread) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'button button--positive review-resolve-button';
        button.textContent = 'Resolve';
        button.onclick = () => {
            this.showResolveConfirmation(thread);
        };
        return button;
    }

	createSidebar() {
		const sidebar = document.createElement('div');
		sidebar.className = 'review-sidebar';
		sidebar.innerHTML = `
			<div class="review-sidebar-header">
				<span>
					<span class="material-icons">reviews</span> 
					Reviews
				</span>
				<span>
					<a href="#" class="material-icons review-toggle-resolved">filter_list</a>
					<a href="#" id="review-sidebar-close" class="material-icons">close</a>
				</span>
			</div>
			<div class="review-sidebar-body">
			</div>
		`;

		const container = document.querySelector(this.sidebarContainer);
		if (container) {
			container.appendChild(sidebar);
		} else {
			document.body.appendChild(sidebar);
		}

		this.sidebar = sidebar;

		const toggle = sidebar.querySelector('.review-toggle-resolved');
		toggle.onclick = event => {
			event.preventDefault();
			this.showResolved = !this.showResolved;
			toggle.textContent = this.showResolved
				? 'filter_list_off'
				: 'filter_list';
			this.showThreadList();
		};
		
		const closeBtn = sidebar.querySelector('#review-sidebar-close');
        if (closeBtn) {
            closeBtn.onclick = event => {
                event.preventDefault();
                this.toggleSidebar();
            };
        }
	}

    createStatusBarButton() {
		const bar = this.mde.element.parentElement.querySelector('.editor-statusbar');
		if (!bar) return;

		const button = document.createElement('span');
		button.className = 'review-status-button';
		button.style.cursor = 'pointer';
		button.onclick = () => {
			this.showThreadList();
		};

		bar.appendChild(button);
		this.statusButton = button;
		this.updateStatusButton();
	}

    async createThread(message) {
        const from = this.cm.indexFromPos(this.cm.getCursor('from'));
        const to = this.cm.indexFromPos(this.cm.getCursor('to'));
        const selectedText = this.cm.getSelection();
        const content = this.cm.getValue();

        const contextBefore = content.substring(Math.max(0, from - 50), from);
        const contextAfter = content.substring(to, Math.min(content.length, to + 50));

        try {
            const response = await fetch(`${this.endpoint}/page/${this.pageId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    startOffset: from,
                    endOffset: to,
                    selectedText,
                    contextBefore,
                    contextAfter,
                    message
                })
            });

            if (!response.ok) throw new Error('Server error handling thread creation');

            const thread = await response.json();
            this.threads.push(thread);
            this.renderThreads();
            this.updateStatusButton();
            this.hideCommentButton();
            this.openThread(thread);
        } catch (error) {
            console.error('Failed to create review thread:', error);
            alert('Failed to save your review.');
        }
    }

    findThreadById(id) {
		return this.threads.find(thread => String(thread.id) === String(id));
    }

    hideCommentButton() {
        this.commentButton.style.display = 'none';
    }

    jumpToThread(thread) {
        const startIdx = thread.currentStartOffset ?? thread.startOffset;
        const endIdx = thread.currentEndOffset ?? thread.endOffset;
        if (startIdx === undefined || endIdx === undefined) return;

        const from = this.cm.posFromIndex(startIdx);
        const to = this.cm.posFromIndex(endIdx);

        this.cm.focus();
        this.cm.setSelection(from, to);
        this.cm.scrollIntoView(from, 150);
    }

	async loadReviewers() {
		const response = await fetch(`${this.endpoint}/reviewers`);
		if (!response.ok) throw new Error('Failed to fetch reviewers');
		this.reviewers =await response.json();
	}

    async loadThreads() {
        const response = await fetch(`${this.endpoint}/page/${this.pageId}`);
		if (!response.ok) throw new Error('Failed to fetch threads');
        this.threads = await response.json();
        this.renderThreads();
		this.updateStatusButton();
    }

    openCommentDialog() {
        const body = this.sidebar.querySelector('.review-sidebar-body');
        body.innerHTML = '';

        const textarea = this.ui('textarea', {
            className: 'review-new-thread-message',
            rows: '5',
            placeholder: 'Enter review comment...'
        });

        const submitBtn = this.ui('button', {
            type: 'button',
            className: 'button button--add',
            onclick: async () => {
                const message = textarea.value.trim();
                if (!message) return;
                submitBtn.disabled = true;
                await this.createThread(message);
            }
        }, 'Create Review');

        const wrapper = this.ui('div', { className: 'review-new-thread' },
            this.ui('h3', {}, 'Create Review'),
            this.ui('div', { className: 'review-selection-preview' }, this.cm.getSelection()),
            textarea,
            submitBtn
        );

        body.appendChild(wrapper);
    }

    openThread(thread) {
        this.activeThreadId = thread.id;
        const body = this.sidebar.querySelector('.review-sidebar-body');
        body.innerHTML = '';

        // Back link header
        body.appendChild(
            this.ui('div', { className: 'review-thread-header' },
                this.ui('a', {
                    href: '#',
                    className: 'review-back-link',
                    onclick: (e) => { e.preventDefault(); this.showThreadList(); }
                }, '← All Reviews')
            )
        );

        if (thread.needsRebase) {
            body.appendChild(this.ui('div', { className: 'review-warning' }, 
                '⚠ This review could not be automatically relocated after content changes.'
            ));
        }
		
		const rawText = thread.selectedText || '';
        const isLongText = rawText.length > 120 || rawText.includes('\n');

		const summaryText = isLongText 
            ? rawText.split('\n').slice(0, 2).join('\n').substring(0, 120).trim() + '...'
            : rawText;

		const blockquote = this.ui('blockquote', { 
            className: isLongText ? 'review-preview-truncatable' : '',
            title: isLongText ? 'Click to expand full selection text' : '',
            style: isLongText ? 'cursor: pointer;' : '',
            onclick: () => {
                if (!isLongText) return;
                // Toggle between full text and summary view on click
                const isExpanded = blockquote.dataset.expanded === 'true';
                blockquote.textContent = isExpanded ? summaryText : rawText;
                blockquote.dataset.expanded = isExpanded ? 'false' : 'true';
            }
        }, summaryText);

        // Selected context block
        body.appendChild(
            this.ui('div', { className: 'review-thread-title' },
                this.ui('strong', {}, 'Selected text'),
                blockquote
            )
        );

        body.appendChild(this.createAssignmentBox(thread));

        // Thread comments iteration
        if (thread.comments?.length) {
            thread.comments.forEach(comment => {
                body.appendChild(
                    this.ui('div', { className: 'review-comment' },
                        this.ui('div', { className: 'review-comment-author' }, comment.author?.name || 'Unknown'),
                        this.ui('div', { className: 'review-comment-message' }, comment.message)
                    )
                );
            });
        }

        if (!thread.resolved) {
            body.appendChild(this.createReplyForm(thread));
            body.appendChild(this.createResolveButton(thread));
        } else {
            body.appendChild(this.createReopenButton(thread));
        }
        
        setTimeout(() => {
            this.renderThreads();
        }, 0);
    }

    renderThreads() {
		this.syncMarkerOffsets();
        this.clearMarkers();

        this.threads.forEach(thread => {
            if (thread.resolved) return;

			// Handle edge cases where offsets might be missing or out of bounds
            const startIdx = thread.currentStartOffset ?? thread.startOffset ?? 0;
            const endIdx = thread.currentEndOffset ?? thread.endOffset ?? 0;

			if (startIdx === endIdx && startIdx === 0) return;

            const marker = this.cm.markText(
				this.cm.posFromIndex(startIdx),
                this.cm.posFromIndex(endIdx),
                {
                    className: thread.id === this.activeThreadId
						? 'cm-review-highlight-active'
						: 'cm-review-highlight',
                    attributes: {
                        'data-thread-id': String(thread.id)
                    }
                }
            );
            marker.reviewThread = thread;
            this.markers.push(marker);
        });
    }

    async reopenThread(threadId) {
        try {
            const response = await fetch(`${this.endpoint}/thread/${threadId}/reopen`, { method: 'POST' });
            if (!response.ok) throw new Error('Server error reopening thread');

            await this.loadThreads();
            const thread = this.findThreadById(threadId);
            if (thread) this.openThread(thread);
        } catch (error) {
            console.error(error);
            alert('Failed to reopen review');
        }
    }

    async replyToThread(threadId, message) {
        try {
            const response = await fetch(`${this.endpoint}/thread/${threadId}/reply`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            });

            if (!response.ok) throw new Error('Server error adding reply');

            await this.loadThreads();
            this.updateStatusButton();
            const thread = this.findThreadById(threadId);
            if (thread) this.openThread(thread);
        } catch (error) {
            console.error(error);
            alert('Failed to add reply');
        }
    }

    showCommentButton() {
        const selection = window.getSelection();
        if (!selection.rangeCount) return;

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

    showResolveConfirmation(thread) {
        const body = this.sidebar.querySelector('.review-sidebar-body');
        const existing = body.querySelector('.review-resolve-confirmation');
        if (existing) { return existing.remove(); }

        const confirmBtn = this.ui('button', {
            type: 'button',
            className: 'button button--positive',
            onclick: async () => {
                confirmBtn.disabled = true;
                await this.resolveThread(thread.id);
            }
        }, 'Resolve');

        const container = this.ui('div', { className: 'review-resolve-confirmation' },
            this.ui('p', {}, 'Resolve this review?'),
            confirmBtn,
            this.ui('button', {
                type: 'button',
                className: 'button',
                onclick: () => container.remove()
            }, 'Cancel')
        );

        body.appendChild(container);
    }

    showThreadList() {
        const body = this.sidebar.querySelector('.review-sidebar-body');
        this.activeThreadId = null;
        body.innerHTML = '';

        const visibleThreads = this.threads.filter(
			thread => this.showResolved || !thread.resolved
		);

        if (!visibleThreads.length) {
            body.innerHTML = '<p class="review-empty-state">No open reviews</p>';
			body.appendChild(this.ui('p', {
				className: 'review-empty-state'
			}, 'No open reviews'));
            return;
        }

        visibleThreads.forEach(thread => {
            const item = this.ui('div', {
                className: 'review-thread-item',
                onclick: () => {
                    this.jumpToThread(thread);
                    this.openThread(thread);
                }
            },
                this.ui('strong', {}, (thread.selectedText || '').substring(0, 60)),
                thread.resolved && this.ui('span', { className: 'review-status-resolved badge badge__ok' }, 'Resolved'),
                this.ui('div', { className: 'review-comment-count' }, `${thread.comments?.length || 0} comment(s)`)
            );

            body.appendChild(item);
        });
    }
	
	syncMarkerOffsets() {
        this.markers.forEach(marker => {
            const pos = marker.find();
            if (pos && marker.reviewThread) {
                marker.reviewThread.currentStartOffset = this.cm.indexFromPos(pos.from);
                marker.reviewThread.currentEndOffset = this.cm.indexFromPos(pos.to);
            }
        });
    }

	toggleSidebar() {
		const container = document.querySelector(this.sidebarContainer);
        if (container) {
            container.classList.toggle('visually-hidden');
        }
	}

    async resolveThread(threadId) {
        try {
            const response = await fetch(`${`${this.endpoint}/thread/${threadId}/resolve`}`, { method: 'POST' });
            if (!response.ok) throw new Error('Server error resolving thread');

            await this.loadThreads();
            this.updateStatusButton();

            const body = this.sidebar.querySelector('.review-sidebar-body');
            body.innerHTML = '';
            
            const successMsg = document.createElement('p');
            successMsg.textContent = 'Review resolved.';
            body.appendChild(successMsg);

            // Return to main list view after 1.5 seconds automatically
            setTimeout(() => this.showThreadList(), 1500);
        } catch (error) {
            console.error(error);
            alert('Failed to resolve review');
        }
    }

	/**
     * Helper to declaratively build DOM elements safely.
     * @param {string} type - HTML Tag name (e.g., 'div', 'button')
     * @param {Object} props - HTML attributes, className, or event listeners (e.g., onclick)
     * @param {...(HTMLElement|string)} children - Child elements or text strings
     */
    ui(type, props = {}, ...children) {
        const el = document.createElement(type);
        
        Object.entries(props).forEach(([key, value]) => {
            if (key.startsWith('on') && typeof value === 'function') {
                // Attach event listeners (e.g., onclick -> click)
                el.addEventListener(key.substring(2).toLowerCase(), value);
            } else if (key === 'className') {
                el.className = value;
            } else if (value !== undefined && value !== null) {
                el.setAttribute(key, String(value));
            }
        });

        children.flat().forEach(child => {
            if (child === undefined || child === null || child === false) return;
            if (typeof child === 'string' || typeof child === 'number') {
                el.appendChild(document.createTextNode(String(child)));
            } else {
                el.appendChild(child);
            }
        });

        return el;
    }

    updateStatusButton() {
        if (!this.statusButton) return;
        const openCount = this.threads.filter(thread => !thread.resolved).length;
        this.statusButton.textContent = `💬 Reviews (${openCount})`;
    }
};
