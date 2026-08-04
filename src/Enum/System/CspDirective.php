<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Enum\System;

enum CspDirective: string
{
    case DefaultSrc = 'default-src';
    case ScriptSrc = 'script-src';
    case ScriptSrcElem = 'script-src-elem';
    case ScriptSrcAttr = 'script-src-attr';
    case StyleSrc = 'style-src';
    case StyleSrcElem = 'style-src-elem';
    case StyleSrcAttr = 'style-src-attr';
    case ImgSrc = 'img-src';
    case FontSrc = 'font-src';
    case ConnectSrc = 'connect-src';
    case MediaSrc = 'media-src';
    case FrameSrc = 'frame-src';
    case FrameAncestors = 'frame-ancestors';
    case WorkerSrc = 'worker-src';
    case ManifestSrc = 'manifest-src';
    case ObjectSrc = 'object-src';
    case FormAction = 'form-action';
    case BaseUri = 'base-uri';

    public function displayName(): string
    {
        return match ($this) {
            self::DefaultSrc => 'Default Resources',
            self::ScriptSrc => 'JavaScript',
            self::ScriptSrcElem => 'External JavaScript',
            self::ScriptSrcAttr => 'Inline JavaScript Events',
            self::StyleSrc => 'Stylesheets',
            self::StyleSrcElem => 'External Stylesheets',
            self::StyleSrcAttr => 'Inline Styles',
            self::ImgSrc => 'Images',
            self::FontSrc => 'Web Fonts',
            self::ConnectSrc => 'API & Network Connections',
            self::MediaSrc => 'Audio & Video',
            self::FrameSrc => 'Embedded Pages',
            self::FrameAncestors => 'Who Can Embed This Site',
            self::WorkerSrc => 'Background Workers',
            self::ManifestSrc => 'Web App Manifest',
            self::ObjectSrc => 'Plugins & Embedded Objects',
            self::FormAction => 'Form Destinations',
            self::BaseUri => 'Base URL',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DefaultSrc => 'Fallback policy for any resource type without its own directive.',
            self::ScriptSrc => 'Controls JavaScript files and inline scripts.',
            self::ScriptSrcElem => 'Controls JavaScript loaded via <script> elements.',
            self::ScriptSrcAttr => 'Controls inline JavaScript event handlers such as onclick.',
            self::StyleSrc => 'Controls CSS files and inline styles.',
            self::StyleSrcElem => 'Controls stylesheets loaded via <link> and <style> elements.',
            self::StyleSrcAttr => 'Controls inline style attributes.',
            self::ImgSrc => 'Controls images, favicons and similar image resources.',
            self::FontSrc => 'Controls downloadable web fonts.',
            self::ConnectSrc => 'Controls Fetch, XHR, EventSource and WebSocket connections.',
            self::MediaSrc => 'Controls audio and video resources.',
            self::FrameSrc => 'Controls which pages can be embedded in frames and iframes.',
            self::FrameAncestors => 'Controls which websites are allowed to embed your pages.',
            self::WorkerSrc => 'Controls Web Workers, Shared Workers and Service Workers.',
            self::ManifestSrc => 'Controls the web application manifest.',
            self::ObjectSrc => 'Controls legacy plugins and embedded objects (typically disabled).',
            self::FormAction => 'Controls where HTML forms are allowed to submit.',
            self::BaseUri => 'Controls which URLs may be used in the document\'s <base> element.',
        };
    }

    public function placeholder(): string
    {
        return match ($this) {
            self::ObjectSrc => 'none',
            default => 'self https://cdn.example.com',
        };
    }

    /**
     * Directives typically shown in the main configuration UI.
     *
     * @return self[]
     */
    public static function primary(): array
    {
        return [
            self::DefaultSrc,
            self::ScriptSrc,
            self::StyleSrc,
            self::ImgSrc,
            self::FontSrc,
            self::ConnectSrc,
            self::FrameSrc,
        ];
    }

    /**
     * Less commonly configured directives.
     *
     * @return self[]
     */
    public static function advanced(): array
    {
        return [
            self::ScriptSrcElem,
            self::ScriptSrcAttr,
            self::StyleSrcElem,
            self::StyleSrcAttr,
            self::MediaSrc,
            self::WorkerSrc,
            self::ManifestSrc,
            self::FrameAncestors,
            self::ObjectSrc,
            self::FormAction,
            self::BaseUri,
        ];
    }
}
