window.Inachis.AiGenerate = {
    options: {
        // CSRF meta tag selector
        csrfMetaSelector: 'meta[name="csrf-token"]',

        // SEO Metadata Generator options
        seoSelector: '#post_generate_seo',
        seoEndpoint: '/incp/api/post/generate-seo-metadata',
        seoTitleSelector: '#post_title',
        seoContentSelector: '#post_content',
        seoSnippetSelector: '#post_featureSnippet',
        seoTagsSelector: '#post_tags',

        // Image Metadata / Alt Text Generator options
        imageSelector: '#resource_generate_alt_text, #generate_alt_text',
        imageEndpointPattern: '/incp/resource/image/{id}/generate-metadata',
        imageTitleSelector: '#resource_title',
        imageAltSelector: '#resource_altText',
        imageDescSelector: '#resource_description',
    },

    /**
     * Initializes the AI generators by scanning for trigger elements.
     * @param {Object} options - Custom configuration overrides
     */
    init: function (options) {
        this.options = { ...this.options, ...options };

        this.initSeoGenerator();
        this.initImageMetadataGenerator();
    },

    /**
     * Binds the click handler to the SEO Generation button if present.
     */
    initSeoGenerator: function () {
        const seoBtn = document.querySelector(this.options.seoSelector);
        if (!seoBtn) return;

        seoBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            const titleVal = document.querySelector(this.options.seoTitleSelector)?.value || '';
            const contentVal = this.getContentValue(this.options.seoContentSelector);

            if (!contentVal.trim()) {
                this.showError('Please enter some article content before generating SEO metadata.');
                return;
            }

            const endpoint = seoBtn.dataset.endpoint || this.options.seoEndpoint;

            this.setButtonLoading(seoBtn, true, 'Generating...');

            try {
                const result = await this.postData(endpoint, {
                    title: titleVal,
                    content: contentVal
                });

                if (result.success && result.data) {
                    // Populate snippet / meta description
                    const snippetField = document.querySelector(this.options.seoSnippetSelector);
                    if (snippetField) {
                        snippetField.value = result.data.excerpt || result.data.metaDescription || '';
                    }

                    // Populate tags if empty
                    const tagsField = document.querySelector(this.options.seoTagsSelector);
                    if (tagsField && Array.isArray(result.data.keywords) && result.data.keywords.length > 0) {
                        if (!tagsField.value.trim()) {
                            tagsField.value = result.data.keywords.join(', ');
                        }
                    }
                } else {
                    throw new Error(result.error || 'Failed to generate SEO metadata.');
                }
            } catch (err) {
                if (err.status === 429) {
                    this.showError(
                        'AI generation is temporarily unavailable. Please try again in a moment.'
                    );
                } else {
                    this.showError('AI Error: ' + err.message);
                }
            } finally {
                this.setButtonLoading(seoBtn, false);
            }
        });
    },

    /**
     * Binds the click handler to the Image Alt Text / Metadata button if present.
     */
    initImageMetadataGenerator: function () {
        const imageBtn = document.querySelector(this.options.imageSelector);
        if (!imageBtn) return;

        imageBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            const imageId = imageBtn.dataset.imageId || imageBtn.dataset.id;
            if (!imageId) {
                this.showError('AI Error: Missing image ID attribute for AI metadata generation.');
                return;
            }

            // Build dynamic endpoint URL using image ID
            let endpoint = imageBtn.dataset.endpoint || this.options.imageEndpointPattern;
            endpoint = endpoint.replace('{id}', encodeURIComponent(imageId));

            this.setButtonLoading(imageBtn, true, 'Analyzing...');

            try {
                const result = await this.postData(endpoint, {});

                if (result.success && result.data) {
                    const titleField = document.querySelector(this.options.imageTitleSelector);
                    const altField = document.querySelector(this.options.imageAltSelector);
                    const descField = document.querySelector(this.options.imageDescSelector);

                    if (titleField && result.data.title) {
                        titleField.value = result.data.title;
                    }
                    if (altField && result.data.altText) {
                        altField.value = result.data.altText;
                    }
                    if (descField && result.data.description) {
                        descField.value = result.data.description;
                    }
                } else {
                    throw new Error(result.error || 'Failed to generate image metadata.');
                }
            } catch (err) {
                if (err.status === 429) {
                    this.showError(
                        'AI generation is temporarily unavailable. Please try again in a moment.'
                    );
                } else {
                    this.showError('AI Error: ' + err.message);
                }
            } finally {
                this.setButtonLoading(imageBtn, false);
            }
        });
    },

    /**
     * Helper to extract content value from textarea or Markdown editor instance.
     */
    getContentValue: function (selector) {
        // If EasyMDE/SimpleMDE is initialized globally
        if (window.mdeEditor && typeof window.mdeEditor.value === 'function') {
            return window.mdeEditor.value();
        }

        const textarea = document.querySelector(selector);
        return textarea ? textarea.value : '';
    },

    /**
     * Generic JSON POST helper with CSRF and XHR headers.
     */
    postData: async function (url, payload) {
        const csrfToken = document.querySelector(this.options.csrfMetaSelector)?.getAttribute('content') || '';

        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(payload)
        });

        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error(`HTTP ${response.status}: Generation failed.`);
        }

        if (!response.ok) {
            const error = new Error(
                data.error || `HTTP ${response.status}: Generation failed.`
            );

            error.status = response.status;

            throw error;
        }

        return data;
    },

    /**
     * Helper to toggle button loading state.
     */
    setButtonLoading: function (button, isLoading, loadingText = 'Processing...') {
        if (isLoading) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
            button.innerHTML = `<span class="material-icons spin">autorenew</span> <span>${loadingText}</span>`;
        } else {
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
                delete button.dataset.originalHtml;
            }
            button.disabled = false;
            button.setAttribute('aria-disabled', 'false');
        }
    },

    /**
     * Displays an AI generation error message.
     *
     * @param {string} message - The message to display.
     */
    showError: function (message) {
        const mainContent = document.querySelector('main.content');
        if (!mainContent) {
            return;
        }

        // Remove an existing AI error message.
        mainContent.querySelector('.ai-generation-error')?.remove();

        const messageElement = document.createElement('div');
        messageElement.className = 'col-12 flash-error ai-generation-error';
        messageElement.setAttribute('aria-live', 'polite');

        const icon = document.createElement('span');
        icon.className = 'material-icons';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = 'error';

        messageElement.appendChild(icon);
        messageElement.append(` ${message}`);

        mainContent.prepend(messageElement);
    },
};
