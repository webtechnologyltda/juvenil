import fs from 'node:fs';
import path from 'node:path';
import gifenc from 'gifenc';

const { GIFEncoder, quantize, applyPalette } = gifenc;

const width = 96;
const height = 96;
const frameCount = 14;
const outputPath = path.resolve('public/img/campfire-loader.gif');

const colors = {
    bg: [5, 47, 53, 255],
    shadow: [3, 24, 28, 255],
    coal: [78, 40, 24, 255],
    log: [117, 66, 32, 255],
    logLight: [192, 100, 38, 255],
    orange: [244, 107, 18, 255],
    amber: [255, 152, 41, 255],
    yellow: [255, 221, 113, 255],
    blue: [157, 219, 239, 255],
};

function createFrame(frameIndex) {
    const pixels = new Uint8Array(width * height * 4);
    const flicker = Math.sin((frameIndex / frameCount) * Math.PI * 2);
    const pulse = 1 + flicker * 0.05;

    fillRect(pixels, 0, 0, width, height, colors.bg);
    drawEllipse(pixels, 48, 70, 34 + flicker * 2, 10, colors.shadow);

    drawLog(pixels, 20, 66, 58, 8, -0.27);
    drawLog(pixels, 24, 70, 54, 8, 0.28);
    drawLog(pixels, 30, 73, 36, 7, 0);

    const flameShift = Math.round(flicker * 3);
    const flameLift = Math.round(Math.sin((frameIndex / frameCount) * Math.PI * 4) * 2);

    drawPolygon(pixels, [
        [48, 70],
        [31 + flameShift, 55],
        [42, 45 + flameLift],
        [45, 29 - flameLift],
        [56, 43],
        [67 - flameShift, 55],
        [59, 70],
    ], colors.orange);

    drawPolygon(pixels, [
        [47, 68],
        [38 - flameShift, 56],
        [45, 45],
        [50 + flameShift, 34 + flameLift],
        [56, 50],
        [61, 59],
        [55, 68],
    ], colors.amber);

    drawPolygon(pixels, [
        [48, 66],
        [43, 57],
        [48 + flameShift, 48 - flameLift],
        [53, 58],
        [52, 66],
    ], colors.yellow);

    drawPolygon(pixels, [
        [43, 70],
        [47, 58],
        [52, 70],
    ], colors.blue);

    for (let i = 0; i < 7; i++) {
        const angle = frameIndex * 0.62 + i * 1.7;
        const x = Math.round(48 + Math.cos(angle) * (14 + i * 1.8) * pulse);
        const y = Math.round(44 + Math.sin(angle * 0.7) * 4 - i * 3 - frameIndex * 0.3);
        const size = i % 3 === 0 ? 2 : 1;
        fillRect(pixels, x, y, size, size, i % 2 === 0 ? colors.amber : colors.yellow);
    }

    return pixels;
}

function fillRect(pixels, x, y, rectWidth, rectHeight, color) {
    for (let row = Math.max(0, y); row < Math.min(height, y + rectHeight); row++) {
        for (let col = Math.max(0, x); col < Math.min(width, x + rectWidth); col++) {
            setPixel(pixels, col, row, color);
        }
    }
}

function drawEllipse(pixels, cx, cy, rx, ry, color) {
    for (let y = Math.floor(cy - ry); y <= Math.ceil(cy + ry); y++) {
        for (let x = Math.floor(cx - rx); x <= Math.ceil(cx + rx); x++) {
            const dx = (x - cx) / rx;
            const dy = (y - cy) / ry;

            if (dx * dx + dy * dy <= 1) {
                setPixel(pixels, x, y, color);
            }
        }
    }
}

function drawLog(pixels, x, y, logWidth, logHeight, rotation) {
    const cx = x + logWidth / 2;
    const cy = y + logHeight / 2;
    const sin = Math.sin(rotation);
    const cos = Math.cos(rotation);

    for (let row = y - 8; row < y + logHeight + 8; row++) {
        for (let col = x - 8; col < x + logWidth + 8; col++) {
            const tx = col - cx;
            const ty = row - cy;
            const localX = tx * cos + ty * sin;
            const localY = -tx * sin + ty * cos;

            if (Math.abs(localX) <= logWidth / 2 && Math.abs(localY) <= logHeight / 2) {
                setPixel(pixels, col, row, colors.log);

                if (Math.abs(localY) < 1) {
                    setPixel(pixels, col, row, colors.logLight);
                }
            }
        }
    }
}

function drawPolygon(pixels, points, color) {
    const minY = Math.floor(Math.min(...points.map((point) => point[1])));
    const maxY = Math.ceil(Math.max(...points.map((point) => point[1])));

    for (let y = minY; y <= maxY; y++) {
        const intersections = [];

        for (let i = 0; i < points.length; i++) {
            const [x1, y1] = points[i];
            const [x2, y2] = points[(i + 1) % points.length];

            if ((y1 <= y && y2 > y) || (y2 <= y && y1 > y)) {
                intersections.push(x1 + ((y - y1) * (x2 - x1)) / (y2 - y1));
            }
        }

        intersections.sort((a, b) => a - b);

        for (let i = 0; i < intersections.length; i += 2) {
            const start = Math.ceil(intersections[i]);
            const end = Math.floor(intersections[i + 1] ?? start);

            for (let x = start; x <= end; x++) {
                setPixel(pixels, x, y, color);
            }
        }
    }
}

function setPixel(pixels, x, y, color) {
    if (x < 0 || y < 0 || x >= width || y >= height) {
        return;
    }

    const index = (Math.round(y) * width + Math.round(x)) * 4;
    pixels[index] = color[0];
    pixels[index + 1] = color[1];
    pixels[index + 2] = color[2];
    pixels[index + 3] = color[3];
}

const gif = GIFEncoder();

for (let frame = 0; frame < frameCount; frame++) {
    const rgba = createFrame(frame);
    const palette = quantize(rgba, 32);
    const index = applyPalette(rgba, palette);

    gif.writeFrame(index, width, height, {
        delay: 82,
        palette,
        repeat: 0,
    });
}

gif.finish();

fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, gif.bytes());

console.log(`Generated ${outputPath}`);
