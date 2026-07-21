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

        return select;
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
            placeholder: 'Reply…',
            rows: '1'
        });

        // Auto-expand on input
        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = `${textarea.scrollHeight}px`;
        });

        const btn = this.ui('button', {
            type: 'button',
            className: 'button button--add review-reply-button',
            onclick: async () => {
                const message = textarea.value.trim();
                if (!message) return;
                btn.disabled = true;
                await this.replyToThread(thread.id, message);

                // Reset size after sending
                textarea.value = '';
                textarea.style.height = 'auto';
            }
        }, 'Reply');

        return this.ui('div', { className: 'review-reply-form' }, textarea, btn);
    }

    createResolveButton(thread) {
        return this.ui(
            'button',
            {
                type: 'button',
                className: 'button button--positive review-resolve-button',
                onclick: () => this.showResolveConfirmation(thread),
            },
            this.ui(
                'span',
                { className: 'material-icons' },
                'check'
            ),
            'Resolve'
        );
    }

	createSidebar() {
        const toggle = this.ui(
            'a',
            {
                href: '#',
                className: 'material-icons review-toggle-resolved',
                onclick: event => {
                    event.preventDefault();
                    this.showResolved = !this.showResolved;
                    toggle.textContent = this.showResolved
                        ? 'filter_list_off'
                        : 'filter_list';

                    this.showThreadList();
                },
            },
            'filter_list'
        );

        const closeBtn = this.ui(
            'a',
            {
                href: '#',
                id: 'review-sidebar-close',
                className: 'material-icons',
                onclick: event => {
                    event.preventDefault();
                    this.toggleSidebar();
                },
            },
            'close'
        );

        const sidebar = this.ui(
            'div',
            { className: 'review-sidebar' },

            this.ui(
                'div',
                { className: 'review-sidebar-header' },

                this.ui(
                    'span',
                    {},
                    this.ui(
                        'span',
                        { className: 'material-icons' },
                        'reviews'
                    ),
                    'Reviews'
                ),

                this.ui(
                    'span',
                    {},
                    toggle,
                    closeBtn
                )
            ),

            this.ui(
                'div',
                { className: 'review-sidebar-body' }
            )
        );

        const container = document.querySelector(this.sidebarContainer);
        if (container) {
            container.appendChild(sidebar);
        } else {
            document.body.appendChild(sidebar);
        }

        this.sidebar = sidebar;
    }

    createStatusBarButton() {
		const bar = this.mde.element.parentElement.querySelector('.editor-statusbar');
		if (!bar) return;

		const button = document.createElement('span');
		button.className = 'review-status-button';
		button.style.cursor = 'pointer';
		button.onclick = () => {
			this.showThreadList();
            this.toggleSidebar();
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

    /**
     * Formats an ISO string or Date object into a human-readable relative timestamp.
     * @param {string|Date} dateInput
     * @returns {string}
     */
    formatTimeAgo(dateInput) {
        if (!dateInput) return '';

        const date = new Date(dateInput);
        const now = new Date();
        const secondsAgo = Math.floor((now - date) / 1000);

        if (isNaN(secondsAgo)) return '';

        // Less than a minute
        if (secondsAgo < 60) return 'Just now';

        // Minutes (1–59m)
        const minutes = Math.floor(secondsAgo / 60);
        if (minutes < 60) return `${minutes}m ago`;

        // Hours (1–23h)
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;

        // Days (1–6 days)
        const days = Math.floor(hours / 24);
        if (days === 1) return 'Yesterday';
        if (days < 7) return `${days}d ago`;

        // Older than a week: standard date formatting (e.g., "Oct 24" or "Oct 24, 2025")
        const isSameYear = date.getFullYear() === now.getFullYear();
        return date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            ...(isSameYear ? {} : { year: 'numeric' })
        });
    }

    hasOpenReviews() {
        return this.threads.some(thread => !thread.resolved);
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
        this.updatePublishButtonGuard();
    }

    openCommentDialog() {
        const body = this.sidebar.querySelector('.review-sidebar-body');
        body.innerHTML = '';

        const textarea = this.ui('textarea', {
            className: 'review-new-thread-message',
            rows: '1',
            placeholder: 'Enter review comment...'
        });

        // Auto-expand on input
        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = `${textarea.scrollHeight}px`;
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
            this.ui('div', { className: 'review-thread-navigation' },
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

        // --- UNIFIED THREAD CARD ---
        const threadCard = this.ui('div', { className: 'review-thread-card' });

        // 1. Thread Header Toolbar (Assignee selection)
        const headerToolbar = this.ui('div', { className: 'review-card-header-toolbar' },
            this.ui('span', { className: 'review-assign-label' }, 'Assigned to:'),
            !thread.resolved ? this.createAssignmentBox(thread) : this.ui('span', { className: 'review-assignee-name' }, thread.assignedTo?.name || 'Unassigned')
        );
        body.appendChild(headerToolbar);

        // 2. Comments & Replies
        if (thread.comments?.length) {
            const primaryComment = thread.comments[0];
            const primaryTime = this.formatTimeAgo(primaryComment.created);

            // Top primary comment
            threadCard.appendChild(
                this.ui('div', { className: 'review-comment review-comment--primary' },
                    this.ui('div', { className: 'review-comment-header' },
                        this.ui('strong', { className: 'review-comment-author' }, primaryComment.author?.name || 'Unknown'),
                        primaryTime && this.ui('span', { className: 'review-comment-time' }, primaryTime)
                    ),
                    this.ui('div', { className: 'review-comment-message' }, primaryComment.message)
                )
            );

            // Nested replies list
            if (thread.comments.length > 1) {
                const repliesContainer = this.ui('div', { className: 'review-replies-list' });

                thread.comments.slice(1).forEach(reply => {
                    const replyTime = this.formatTimeAgo(reply.created);

                    repliesContainer.appendChild(
                        this.ui('div', { className: 'review-comment review-comment--reply' },
                            this.ui('div', { className: 'review-comment-header' },
                                this.ui('strong', { className: 'review-comment-author' }, reply.author?.name || 'Unknown'),
                                replyTime && this.ui('span', { className: 'review-comment-time' }, replyTime)
                            ),
                            this.ui('div', { className: 'review-comment-message' }, reply.message)
                        )
                    );
                });

                threadCard.appendChild(repliesContainer);
            }
        }

        // 3. Card Footer Actions (Resolve or Reopen)
        const footerToolbar = this.ui('div', { className: 'review-card-footer-toolbar' });
        if (!thread.resolved) {
            footerToolbar.appendChild(this.createResolveButton(thread));
        } else {
            footerToolbar.appendChild(this.createReopenButton(thread));
        }
        threadCard.appendChild(footerToolbar);

        body.appendChild(threadCard);

        // 4. Reply Form (Docked at bottom of thread)
        if (!thread.resolved) {
            body.appendChild(this.createReplyForm(thread));
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

    updatePublishButtonGuard() {
        const publishBtn = document.querySelector('#post_publish');
        if (!publishBtn) return;

        if (this.hasOpenReviews()) {
            const openCount = this.threads.filter(t => !t.resolved).length;

            // Attaching data-confirm triggers your ConfirmationPrompt script
            publishBtn.setAttribute('data-confirm', 'publish');
            publishBtn.setAttribute('data-hideHelp', 'true');
            publishBtn.setAttribute('data-title', 'Unresolved Reviews Warning');
            publishBtn.setAttribute('data-warning', `There are currently ${openCount} unresolved review(s) on this post.`);
            publishBtn.setAttribute('data-confirm-text', 'Publish Anyway');
        } else {
            // Remove data-confirm so normal form submission or action proceeds without a modal
            publishBtn.removeAttribute('data-confirm');
            publishBtn.removeAttribute('data-title');
            publishBtn.removeAttribute('data-warning');
            publishBtn.removeAttribute('data-confirm-text');
        }
    }

    updateStatusButton() {
        if (!this.statusButton) return;
        const openCount = this.threads.filter(thread => !thread.resolved).length;
        this.statusButton.textContent = `💬 Reviews (${openCount})`;
    }
};
