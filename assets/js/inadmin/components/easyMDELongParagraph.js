window.Inachis.EasyMDELongParagraph = class {
    /**
     * @param {CodeMirror.Editor} codeMirror
     * @param {Object} options
     * @param {number} options.threshold
     */
    constructor(codeMirror, options = {}) {
        this.codeMirror = codeMirror;
        this.threshold = options.threshold ?? 150;
        this.marks = new Set();
        this.refreshTimer = null;

        this.handleChange = this.handleChange.bind(this);
    }

    /**
     * Initialise the long paragraph highlighting.
     *
     * @returns {this}
     */
    init() {
        this.codeMirror.on('change', this.handleChange);
        this.refresh();

        return this;
    }

    /**
     * Handle CodeMirror document changes.
     */
    handleChange() {
        window.clearTimeout(this.refreshTimer);

        this.refreshTimer = window.setTimeout(() => {
            this.refresh();
        }, 150);
    }

    /**
     * Refresh all long paragraph highlights.
     */
    refresh() {
        this.clearMarks();

        const lineCount = this.codeMirror.lineCount();

        let paragraphStart = null;
        let paragraphLines = [];
        let fencedCode = false;
        let fenceCharacter = null;

        const processParagraph = (endLine) => {
            if (paragraphStart === null || paragraphLines.length === 0) {
                return;
            }

            const text = paragraphLines.join('\n');
            const wordCount = this.countWords(text);

            if (wordCount <= this.threshold) {
                return;
            }

            const lastLine = endLine - 1;

            const mark = this.codeMirror.markText(
                {
                    line: paragraphStart,
                    ch: 0,
                },
                {
                    line: lastLine,
                    ch: this.codeMirror.getLine(lastLine).length,
                },
                {
                    className: 'cm-long-paragraph',
                    title: `Long paragraph: ${wordCount} words. Consider breaking this into smaller paragraphs.`,
                }
            );

            this.marks.add(mark);
        };

        const resetParagraph = (line) => {
            processParagraph(line);

            paragraphStart = null;
            paragraphLines = [];
        };

        for (let line = 0; line < lineCount; line++) {
            const text = this.codeMirror.getLine(line);
            const trimmed = text.trim();

            /*
             * Handle fenced code blocks.
             *
             * Supports:
             *
             *     ```
             *     ```php
             *     ~~~
             *     ~~~javascript
             */
            const fence = this.getFence(trimmed);

            if (fence) {
                resetParagraph(line);

                if (!fencedCode) {
                    fencedCode = true;
                    fenceCharacter = fence.character;
                } else if (fence.character === fenceCharacter) {
                    fencedCode = false;
                    fenceCharacter = null;
                }

                continue;
            }

            /*
             * Anything inside a fenced code block is ignored.
             */
            if (fencedCode) {
                continue;
            }

            /*
             * Blank lines terminate a paragraph.
             */
            if (trimmed === '') {
                resetParagraph(line);

                continue;
            }

            /*
             * Markdown constructs that aren't normal prose paragraphs
             * terminate the current paragraph.
             */
            if (this.isNonParagraphLine(text)) {
                resetParagraph(line);

                continue;
            }

            if (paragraphStart === null) {
                paragraphStart = line;
                paragraphLines = [];
            }

            paragraphLines.push(text);
        }

        processParagraph(lineCount);
    }

    /**
     * Determine whether a line is a Markdown fenced-code delimiter.
     *
     * Supports both backtick and tilde fences, with optional language
     * identifiers or other trailing content.
     *
     * @param {string} line
     *
     * @returns {{character: string, length: number}|null}
     */
    getFence(line) {
        const match = line.match(/^ {0,3}(`{3,}|~{3,})(?:.*)?$/);

        if (!match) {
            return null;
        }

        return {
            character: match[1][0],
            length: match[1].length,
        };
    }

    /**
     * Determine whether a line is not part of a normal prose paragraph.
     *
     * @param {string} line
     *
     * @returns {boolean}
     */
    isNonParagraphLine(line) {
        const trimmed = line.trim();

        if (!trimmed) {
            return true;
        }

        return (
            /^#{1,6}\s/.test(trimmed) ||       // Heading
            /^[-*+]\s+/.test(trimmed) ||       // Unordered list
            /^\d+[.)]\s+/.test(trimmed) ||     // Ordered list
            /^>\s?/.test(trimmed) ||           // Blockquote
            /^ {4}/.test(line) ||              // Indented code
            /^---+$/.test(trimmed) ||          // Horizontal rule
            /^\*\*\*+$/.test(trimmed) ||
            /^___+$/.test(trimmed)
        );
    }

    /**
     * Count words after removing Markdown formatting.
     *
     * @param {string} text
     *
     * @returns {number}
     */
    countWords(text) {
        const plainText = this.stripMarkdown(text);

        if (!plainText) {
            return 0;
        }

        return plainText
            .split(/\s+/)
            .filter(Boolean)
            .length;
    }

    /**
     * Strip Markdown formatting that should not affect the word count.
     *
     * @param {string} text
     *
     * @returns {string}
     */
    stripMarkdown(text) {
        return text
            // Images.
            .replace(/!\[([^\]]*)\]\([^)]+\)/g, '$1')

            // Links.
            .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')

            // Reference links.
            .replace(/\[([^\]]+)\]\[[^\]]*\]/g, '$1')

            // Inline code.
            .replace(/`([^`]+)`/g, '$1')

            // Strong emphasis.
            .replace(/(\*\*|__)(.*?)\1/g, '$2')

            // Emphasis.
            .replace(/(\*|_)(.*?)\1/g, '$2')

            // Strikethrough.
            .replace(/~~(.*?)~~/g, '$1')

            // HTML.
            .replace(/<[^>]+>/g, ' ')

            // Remaining Markdown punctuation.
            .replace(/[#>*_~`]/g, ' ')

            // Normalise whitespace.
            .replace(/\s+/g, ' ')
            .trim();
    }

    /**
     * Remove all existing CodeMirror marks.
     */
    clearMarks() {
        this.marks.forEach((mark) => {
            mark.clear();
        });

        this.marks.clear();
    }

    /**
     * Destroy the highlighter.
     */
    destroy() {
        window.clearTimeout(this.refreshTimer);
        this.codeMirror.off('change', this.handleChange);
        this.clearMarks();
    }
};
