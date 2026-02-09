import esbuild from "esbuild";
import { sassPlugin } from "esbuild-sass-plugin";
import fs from "fs";
import path from "path";
import imagemin from "imagemin";
import imageminMozjpeg from "imagemin-mozjpeg";
import imageminPngquant from "imagemin-pngquant";
import imageminGifsicle from "imagemin-gifsicle";
import imageminSvgo from "imagemin-svgo";

const isWatch = process.argv.includes("--watch");
const isProd = !isWatch;

(() => {
    const composerJson = JSON.parse(fs.readFileSync(path.resolve('composer.json'), 'utf-8'));
    const versionFilePath = path.resolve('config/version.php');

    const version = composerJson.version || 'dev';
    const commit = process.env.GIT_COMMIT || 'dev';
    const buildDate = new Date().toISOString();

    const versionFileContent = `<?php
return [
    'version' => '${version}',
    'commit' => '${commit}',
    'build_date' => '${buildDate}',
];
`;

    fs.writeFileSync(versionFilePath, versionFileContent);
    console.log(`📄 Generated version file: ${versionFilePath}`);
})();

const jsBaseConfig = {
    bundle: true,
    minify: isProd,
    sourcemap: !isProd,
    format: "iife",
    target: ["es2017"]
};

const scssBaseConfig = {
    bundle: true,
    external: ['*.png'],
    minify: isProd,
    sourcemap: !isProd,
    plugins: [
        sassPlugin({
            outputStyle: "compressed",
        })
    ]
};

const builds = {
    inadmin: {
        js: {
            ...jsBaseConfig,
            entryPoints: ["assets/js/inadmin.js"],
            outfile: "public/assets/js/incc/scripts.min.js"
        },
        scss: {
            ...scssBaseConfig,
            entryPoints: ["assets/scss/inadmin/styles.scss"],
            outfile: "public/assets/css/incc/styles.min.css"
        }
    },
    web: {
        // js: {
        //     ...jsBaseConfig,
        //     entryPoints: ["assets/js/web.js"],
        //     outfile: "public/assets/js/scripts.min.js"
        // },
        // scss: {
        //     ...scssBaseConfig,
        //     entryPoints: ["assets/scss/web/styles.scss"],
        //     outfile: "public/assets/css/styles.min.css"
        // }
    }
};

const color = {
    js: txt => `\x1b[36m${txt}\x1b[0m`,
    css: txt => `\x1b[35m${txt}\x1b[0m`,
    ok: txt => `\x1b[32m${txt}\x1b[0m`,
    err: txt => `\x1b[31m${txt}\x1b[0m`,
};

async function optimizeImages() {
    const inputDir = "assets/imgs/incc";
    const outputDir = "public/assets/imgs/incc";

    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    const files = fs.readdirSync(inputDir).filter(f =>
        /\.(png|jpe?g|gif|svg)$/i.test(f)
    );

    await Promise.all(
        files.map(async file => {
            await imagemin([path.join(inputDir, file)], {
                destination: outputDir,
                plugins: [
                    imageminMozjpeg({ quality: 75 }),
                    imageminPngquant({ quality: [0.7, 0.85] }),
                    imageminGifsicle({ optimizationLevel: 2 }),
                    imageminSvgo()
                ]
            });
            console.log(` - Optimized: ${file}`);
        })
    );
}

async function copyIconsAndManifests() {
    const srcDir = 'assets/imgs/incc';
    const destDir = 'public/assets/imgs/incc';
    const filesToCopy = ['.ico', 'browserconfig.xml', 'site.webmanifest'];

    fs.mkdirSync(destDir, { recursive: true });

    fs.readdirSync(srcDir)
        .filter(file =>
            file.endsWith('.ico') ||
            filesToCopy.includes(file)
        )
        .forEach(file => {
            fs.copyFileSync(path.join(srcDir, file), path.join(destDir, file));
            console.log(`📄 Copied: ${file} to ${destDir}`);
        }
        );
}

async function copyExtraLibraries() {
    const libs = [
        "node_modules/filepond/dist/filepond.min.js",
        "node_modules/filepond/dist/filepond.min.css",
        "node_modules/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js",
        "node_modules/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css",
        "node_modules/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.min.js",
        "node_modules/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.min.js",
        "node_modules/jquery/dist/jquery.min.js",
        "node_modules//jquery-ui-dist/jquery-ui.min.js",
        "node_modules/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js",
        "node_modules/jquery-datetimepicker/build/jquery.datetimepicker.min.css",
        "node_modules/easymde/dist/easymde.min.js",
        "node_modules/tom-select/dist/js/tom-select.complete.min.js"
    ];

    const destDirJs = "public/assets/js/incc/";
    const destDirCss = "public/assets/css/incc/";
    if (!fs.existsSync(destDirJs)) fs.mkdirSync(destDirJs, { recursive: true });
    if (!fs.existsSync(destDirCss)) fs.mkdirSync(destDirCss, { recursive: true });

    libs.forEach(libPath => {
        const fileName = path.basename(libPath);
        let destDir = fileName.endsWith('css') ? destDirCss : destDirJs;
        fs.copyFileSync(libPath, path.join(destDir, fileName));
        console.log(`📄 Copied: ${fileName} to ${destDir}`);
    });
}

function debounce(fn, delay = 100) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

function withTiming(label, fn) {
    return async () => {
        const start = performance.now();
        console.log(`🔄 Rebuilding ${label}...`);

        try {
            await fn();
            const ms = Math.round(performance.now() - start);
            console.log(`✅ ${label} rebuilt in ${ms}ms`);
        } catch (err) {
            console.error(`❌ ${label} rebuild failed`);
            console.error(err);
        }
    };
}

function watchLogger(label, paint) {
    const log = debounce((ms, ok = true) => {
        const msg = ok
            ? color.ok(`✅ ${label} rebuilt in ${ms}ms`)
            : color.err(`❌ ${label} rebuild failed`);

        console.log(paint(msg));
    });

    return {
        name: `${label}-watch-logger`,
        setup(build) {
            build.onStart(() => {
                build.__start = performance.now();
                console.log(paint(`🔄 Rebuilding ${label}...`));
            });

            build.onEnd(result => {
                const ms = Math.round(performance.now() - build.__start);
                log(ms, result.errors.length === 0);
            });
        }
    };
}


async function runWatchMode() {
    console.log("👀 Watch mode enabled\n");

    const inadminJsCtx = await esbuild.context({
        ...builds.inadmin.js,
        plugins: [
            ...(builds.inadmin.js.plugins || []),
            watchLogger("Admin Panel JS", color.js)
        ]
    });

    const inadminScssCtx = await esbuild.context({
        ...builds.inadmin.scss,
        plugins: [
            ...(builds.inadmin.scss.plugins || []),
            watchLogger("Admin Panel SCSS", color.css)
        ]
    });

    await Promise.all([
        inadminJsCtx.watch(),
        inadminScssCtx.watch()
    ]);

    console.log(color.js("📂 assets/js/inadmin/** → JS rebuild"));
    console.log(color.css("🎨 assets/scss/inadmin/** → CSS rebuild"));
    console.log("\n⏳ Waiting for changes...\n");
}

async function runProdBuild() {
    await optimizeImages();
    console.log("✅ Images optimized");

    await copyIconsAndManifests();
    console.log("✅ FavIcons and Manifests copied");

    await Promise.all([
        esbuild.build(builds.inadmin.js),
        esbuild.build(builds.inadmin.scss),
        // esbuild.build(builds.web.js),
        // esbuild.build(builds.web.scss),
    ]);

    await copyExtraLibraries();
    console.log("✅ JS and CSS complete");
}


async function run() {
    try {
        console.log("🚀 Starting build...");

        if (isWatch) {
            await runWatchMode();
            return;
        }
        await runProdBuild();
    } catch (err) {
        console.error("❌ Build failed:", err);
        process.exit(1);
    }
}

run().catch(() => process.exit(1));
