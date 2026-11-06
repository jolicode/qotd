import { Controller } from '@hotwired/stimulus';
import { visit } from '@hotwired/turbo';
import { path } from '../router.js';

/* Clicking on a bar lists the QOTD of its author */
export default class extends Controller {
    connectChart({ detail: { chart } }) {
        chart.options.onClick = (event, elements) => {
            if (0 === elements.length) {
                return;
            }

            visit(path('qotd_index', { authors: [chart.data.datasets[0].usernames[elements[0].index]] }));
        };
        chart.options.onHover = (event, elements) => {
            event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
        };
    }
}
