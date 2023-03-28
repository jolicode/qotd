import { Controller } from '@hotwired/stimulus';
import { Toast } from 'bootstrap';

export default class extends Controller {
    static values = {
        options: Object,
    }

    connect() {
        this.toast = new Toast(this.element, this.optionsValue);
        this.toast.show();
    }

    disconnect() {
        this.toast.hide();
    }
}
