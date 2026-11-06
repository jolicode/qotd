import { Controller } from '@hotwired/stimulus';
import { visit } from '@hotwired/turbo';
import { path } from '../router.js';

/* Clicking on a slice lists the QOTD of the author it stands for */
export default class extends Controller {
    static values = { otherUsersLabel: String };

    connectChart({ detail: { chart } }) {
        const getAuthor = (elements) => {
            const label = chart.data.labels[elements[0]?.index];

            return label === this.otherUsersLabelValue ? undefined : label;
        };

        chart.options.onClick = (event, elements) => {
            const author = getAuthor(elements);

            if (author) {
                visit(path('qotd_index', { author }));
            }
        };
        chart.options.onHover = (event, elements) => {
            event.native.target.style.cursor = getAuthor(elements) ? 'pointer' : 'default';
        };
    }
}
