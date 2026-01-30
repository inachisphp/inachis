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



const builds = [
    // JS
    {
        ...jsBaseConfig,
        entryPoints: ["assets/js/inadmin.js"],
        outfile: "public/assets/js/incc/scripts.min.js"
    },

    {
        ...jsBaseConfig,
        entryPoints: ["assets/js/web.js"],
        outfile: "public/assets/js/scripts.min.js"
    },

    // SCSS
    {
        ...scssBaseConfig,
        entryPoints: ["assets/scss/inadmin/styles.scss"],
        outfile: "public/assets/css/incc/styles.min.css"
    // },
    // {
    //     ...scssBaseConfig,
    //     entryPoints: ["assets/scss/web/styles.scss"],
    //     outfile: "public/assets/css/styles.min.css"
    }
];

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
                    imageminPngquant({ quality: [ 0.7, 0.85 ] }),
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
        "node_modules/dropzone/dist/dropzone-min.js",
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


async function run() {
    try {
        console.log("🚀 Starting build...");

        if (isWatch) {
            const contexts = await Promise.all(
                builds.map(config => esbuild.context(config))
            );
            await Promise.all(contexts.map(ctx => ctx.watch()));
            console.log("👀 Watching for changes...");
        } else {
            await optimizeImages();
            console.log("✅ Images optimized");
            await copyIconsAndManifests();
            console.log("✅ FavIcons and Manifests copied");

            await Promise.all(builds.map(config => esbuild.build(config)));
            await copyExtraLibraries();
            console.log("✅ JS and CSS complete");

            console.log("✅ Build complete")
        }
    } catch (err) {
        console.error("❌ Build failed:", err);
        process.exit(1);
    }
}

run().catch(() => process.exit(1));
