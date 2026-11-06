/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

const MAX_DIMENSION = 1600;

/* Resizes the images in the browser before the upload, and explains the rejected files */
export default class extends Controller {
    static targets = ['message'];

    resize(event) {
        // Keep the original of a file that cannot be resized, without giving up on the others
        event.detail.waitUntil(Promise.all(event.detail.files.map((file) => this.#resize(file).catch(() => file))));
    }

    explainRejection({ detail: { files, reason } }) {
        const names = files.map((file) => file.name).join(', ');

        this.messageTarget.textContent = 'accept' === reason ? `Not an image: ${names}` : `Too many images: ${names}`;
        this.messageTarget.hidden = false;
    }

    hideRejection() {
        this.messageTarget.hidden = true;
    }

    async #resize(file) {
        const bitmap = await createImageBitmap(file);

        try {
            const scale = MAX_DIMENSION / Math.max(bitmap.width, bitmap.height);

            if (scale >= 1) {
                return file;
            }

            const canvas = new OffscreenCanvas(Math.round(bitmap.width * scale), Math.round(bitmap.height * scale));
            canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            const blob = await canvas.convertToBlob({ type: 'image/jpeg', quality: 0.85 });

            return new File([blob], file.name.replace(/\.\w+$/, '.jpg'), { type: 'image/jpeg', lastModified: file.lastModified });
        } finally {
            bitmap.close();
        }
    }
}
