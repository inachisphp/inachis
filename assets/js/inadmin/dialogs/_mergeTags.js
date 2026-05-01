import { Dialog } from '../components/dialog.js';

window.Inachis.MergeTags = {
    buttons: [],
    selectedTagIds: [],

    init() {
        this.mergeTagsLink = document.querySelector('.button--merge');
        if (!this.mergeTagsLink) return;

        this.mergeTagsLink.addEventListener('click', e => {
            e.preventDefault();
            this.open();
        });
    },

    open() {
		this.selectedTagIds = this.getSelectedTagIds();

		if (this.selectedTagIds.length < 2) {
			return;
		}
        this.dialog?.close();

        this.dialog = new Dialog({
			id: 'dialog__mergeTags',
			title: 'Merge Tags',
			className: 'dialog__mergeTags',
			content: `
			<p>&nbsp;</p>
			<div class="loader"></div>
			<p>&nbsp;</p>
			`,
			buttons: [
				{
					text: 'Merge',
					class: 'button button--positive',
					disabled: true,
					click: () => this.submitMerge(this, this.selectedTagIds)
				},
				{
					text: 'Cancel',
					class: 'button button--info',
					click() {
						this.close();
					}
				}
			],
			onOpen: dialog => {
				document.querySelector('.fixed-bottom-bar')?.classList.toggle('hidden');
				this.loadMergeOptions(dialog, this.selectedTagIds);
			},
			onClose: () => {
				document.querySelector('.fixed-bottom-bar')?.classList.toggle('hidden');
			}
        });

        this.dialog.open();

    },

	loadMergeOptions(dialog, tagIds) {
		const items = tagIds.map(id => {
			const label = document.querySelector(`input[value="${id}"]`)
			?.closest('tr')
			?.querySelector('a')
			?.textContent?.trim();

			return { id, label: label || id };
		});

		dialog.setContent(`
			<form id="merge-form">
			<p>Select the tag to <strong>KEEP</strong>. The others will be removed, and the selected tag will replace them in all pages that currently use any of the other tags in this list.</p>

			<div class="merge-list">
				${items.map(t => `
				<label style="display:block;margin:.5rem 0;">
					<input type="radio" name="target" value="${t.id}">
					${t.label}
				</label>
				`).join('')}
			</div>
			</form>
		`);
		const container = dialog.dialog.querySelector('#merge-form');
		const submitBtn = dialog.buttons[0];
		container.addEventListener('change', e => {
			if (!e.target.matches('input[type="radio"]')) return;
			submitBtn.disabled =
				!container.querySelectorAll('input[type="radio"]:checked').length;
		});
	},

	submitMerge(dialog, tagIds) {
		const form = dialog.dialog.dialog.querySelector('#merge-form');
		const target = form.querySelector('input[name="target"]:checked');

		if (!target) {
			return;
		}

		const sources = tagIds.filter(id => id !== target.value);

		const payload = new URLSearchParams();
		payload.append('target', target.value);
		sources.forEach(id => payload.append('sources[]', id));
		fetch('/incc/tags/merge', {
			method: 'POST',
			body: payload
		})
		.then(r => {
			if (!r.ok) throw new Error();
			location.reload();
		})
		.catch(err => {
			const container = document.querySelector('.dialog-body');
			let existing = document.getElementById('error_details');

			const message = err?.message || 'Unknown error';

			if (!existing) {
				const div = document.createElement('div');
				div.id = 'error_details';
				div.className = 'col-12 admonition admonition__warning';
				div.textContent = 'Merge failed: ' + message;

				container.insertBefore(div, container.firstChild);
			} else {
				existing.textContent = 'Merge failed: ' + message;
			}
		});
	},

	getSelectedTagIds() {
		return [...document.querySelectorAll('input[name="items[]"]:checked')]
			.map(i => i.value);
	},
};