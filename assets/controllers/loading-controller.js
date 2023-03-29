import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        document.addEventListener("turbo:before-fetch-request", () => {
            this.element.classList.remove('d-none');
        });
        document.addEventListener("turbo:before-fetch-response", () => {
            this.element.classList.add('d-none');
        });
    }
}
