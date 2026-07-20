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

    async loadThreads() {
        const response = await fetch(`${this.endpoint}/page/${this.pageId}`);
		if (!response.ok) throw new Error('Failed to fetch threads');
        this.threads = await response.json();
        this.renderThreads();
		this.updateStatusButton();
    }

	async loadReviewers() {
		const response = await fetch(`${this.endpoint}/reviewers`);
		if (!response.ok) throw new Error('Failed to fetch reviewers');
		this.reviewers =await response.json();
	}

    renderThreads() {
        this.clearMarkers();
        this.threads.forEach(thread => {
            if (thread.resolved) return;

			// Handle edge cases where offsets might be missing or out of bounds
            const startIdx = thread.currentStartOffset ?? thread.startOffset ?? 0;
            const endIdx = thread.currentEndOffset ?? thread.endOffset ?? 0;

            const marker = this.cm.markText(
				this.cm.posFromIndex(startIdx),
                this.cm.posFromIndex(endIdx),
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

        // Wait for CodeMirror to sync DOM before binding click event listeners
        setTimeout(() => this.attachHighlightEvents(), 0);
    }

    clearMarkers() {
        this.markers.forEach(marker => marker.clear());
        this.markers = [];
    }

    findThreadById(id) {
		return this.threads.find(thread => String(thread.id) === String(id));
    }

    attachHighlightEvents() {
        document.querySelectorAll('[data-thread-id]').forEach(el => {
			if (el.dataset.reviewBound) return;

			el.dataset.reviewBound = '1';
			el.addEventListener('click', (e) => {
                e.stopPropagation();
                const thread = this.findThreadById(el.dataset.threadId);
                if (thread) {
                    this.jumpToThread(thread);
                    this.openThread(thread);
                }
            });
		});
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

	toggleSidebar() {
		const container = document.querySelector(this.sidebarContainer);
        if (container) {
            container.classList.toggle('visually-hidden');
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

    showThreadList() {
        const body = this.sidebar.querySelector('.review-sidebar-body');
        this.activeThreadId = null;
        body.innerHTML = '';

        const visibleThreads = this.threads.filter(thread => this.showResolved || !thread.resolved);

        if (!visibleThreads.length) {
            body.innerHTML = '<p class="review-empty-state">No open reviews</p>';
            return;
        }

        visibleThreads.forEach(thread => {
            const item = document.createElement('div');
            item.className = 'review-thread-item';
            
            // XSS Prevention via context-safe text assignment
            const titleEl = document.createElement('strong');
            titleEl.textContent = (thread.selectedText || '').substring(0, 60);
            item.appendChild(titleEl);

            if (thread.resolved) {
                const badge = document.createElement('span');
                badge.className = 'review-status-resolved badge badge__ok';
                badge.textContent = 'Resolved';
                item.appendChild(badge);
            }

            const countEl = document.createElement('div');
            countEl.className = 'review-comment-count';
            countEl.textContent = `${thread.comments?.length || 0} comment(s)`;
            item.appendChild(countEl);

            item.onclick = () => {
                this.jumpToThread(thread);
                this.openThread(thread);
            };

            body.appendChild(item);
        });
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

    hideCommentButton() {
        this.commentButton.style.display = 'none';
    }

    openCommentDialog() {
        const body = this.sidebar.querySelector('.review-sidebar-body');
        body.innerHTML = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'review-new-thread';
        
        const h3 = document.createElement('h3');
        h3.textContent = 'Create Review';
        wrapper.appendChild(h3);

        const preview = document.createElement('div');
        preview.className = 'review-selection-preview';
        preview.textContent = this.cm.getSelection();
        wrapper.appendChild(preview);

        const textarea = document.createElement('textarea');
        textarea.className = 'review-new-thread-message';
        textarea.rows = 5;
        textarea.placeholder = 'Enter review comment...';
        wrapper.appendChild(textarea);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'button button--add';
        button.textContent = 'Create Review';
        
        button.onclick = async () => {
            const message = textarea.value.trim();
            if (!message) return;

            button.disabled = true; // Prevent double submission
            await this.createThread(message);
        };
        wrapper.appendChild(button);

        body.appendChild(wrapper);
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

    openThread(thread) {
        this.activeThreadId = thread.id;
        const body = this.sidebar.querySelector('.review-sidebar-body');
        body.innerHTML = '';

        // Header Navigation Back Link
        const header = document.createElement('div');
        header.className = 'review-thread-header';
        const backLink = document.createElement('a');
        backLink.href = '#';
        backLink.className = 'review-back-link';
        backLink.textContent = '← All Reviews';
        backLink.onclick = (e) => {
            e.preventDefault();
            this.showThreadList();
        };
        header.appendChild(backLink);
        body.appendChild(header);

        if (thread.needsRebase) {
            const warning = document.createElement('div');
            warning.className = 'review-warning';
            warning.textContent = '⚠ This review could not be automatically relocated after content changes.';
            body.appendChild(warning);
        }

        const title = document.createElement('div');
        title.className = 'review-thread-title';
        const strong = document.createElement('strong');
        strong.textContent = 'Selected text';
        const blockquote = document.createElement('blockquote');
        blockquote.textContent = thread.selectedText;
        title.appendChild(strong);
        title.appendChild(blockquote);
        body.appendChild(title);

        body.appendChild(this.createAssignmentBox(thread));

        if (thread.comments?.length) {
            thread.comments.forEach(comment => {
                const item = document.createElement('div');
                item.className = 'review-comment';

                const author = document.createElement('div');
                author.className = 'review-comment-author';
                author.textContent = comment.author?.name || 'Unknown';

                const msg = document.createElement('div');
                msg.className = 'review-comment-message';
                msg.textContent = comment.message;

                item.appendChild(author);
                item.appendChild(msg);
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

    updateStatusButton() {
        if (!this.statusButton) return;
        const openCount = this.threads.filter(thread => !thread.resolved).length;
        this.statusButton.textContent = `💬 Reviews (${openCount})`;
    }

    createReplyForm(thread) {
        const wrapper = document.createElement('div');
        wrapper.className = 'review-reply-form';

        const textarea = document.createElement('textarea');
        textarea.className = 'review-reply-text';
        textarea.placeholder = 'Reply to this review...';
        textarea.rows = 4;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'button button--add review-reply-button';
        button.textContent = 'Reply';

        button.onclick = async () => {
            const message = textarea.value.trim();
            if (!message) return;
            button.disabled = true;
            await this.replyToThread(thread.id, message);
        };

        wrapper.appendChild(textarea);
        wrapper.appendChild(button);
        return wrapper;
    }

    createAssignmentBox(thread) {
        const wrapper = document.createElement('div');
        wrapper.className = 'review-assignment';

        const select = document.createElement('select');
        select.className = 'review-assignee-select';
        
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = 'Unassigned';
        select.appendChild(defaultOpt);

        this.reviewers.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.name;
            if (thread.assignedTo && String(thread.assignedTo.id) === String(user.id)) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        select.onchange = () => {
            this.assignThread(thread.id, select.value);
        };

        wrapper.appendChild(select);
        return wrapper;
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

    showResolveConfirmation(thread) {
        const body = this.sidebar.querySelector('.review-sidebar-body');
        const existing = body.querySelector('.review-resolve-confirmation');
        if (existing) {
            existing.remove();
            return;
        }

        const container = document.createElement('div');
        container.className = 'review-resolve-confirmation';

        const p = document.createElement('p');
        p.textContent = 'Resolve this review?';
        container.appendChild(p);

        const confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        confirmBtn.className = 'button button--positive';
        confirmBtn.textContent = 'Resolve';
        confirmBtn.onclick = async () => {
            confirmBtn.disabled = true;
            await this.resolveThread(thread.id);
        };

        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'button';
        cancelBtn.textContent = 'Cancel';
        cancelBtn.onclick = () => container.remove();

        container.appendChild(confirmBtn);
        container.appendChild(cancelBtn);
        body.appendChild(container);
    }
};
