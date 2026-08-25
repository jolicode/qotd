import { startStimulusApp } from '@symfony/stimulus-bundle';
import Carousel from 'stimulus-carousel';

const app = startStimulusApp();
app.register('carousel', Carousel);
