import { startStimulusApp } from '@symfony/stimulus-bundle';
import Carousel from 'stimulus-carousel';

const application = startStimulusApp();
application.register('carousel', Carousel);
