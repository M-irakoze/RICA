import './bootstrap';

import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Turbo = Turbo;
window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

