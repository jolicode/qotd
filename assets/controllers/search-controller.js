import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
    async initialize() {
        this.component = await getComponent(this.element);

        this.component.on('render:finished', (component) => {
            const url = new URL(window.location);
            url.searchParams.set("query", component.valueStore.data.query);
            window.history.pushState({}, "", url);
        });
    }
}
