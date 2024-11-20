import './bootstrap';
import './particles.js';
import './countdown.js';

//set dark mode
localStorage.theme = 'dark';

document.addEventListener('livewire:init', () => {
    Livewire.on('inscricao-realizada', (event) => {
        document.getElementById('registration').scrollIntoView({
            behavior: 'smooth',
        });
    });
});

jQuery('#clockForm').countdown('2024/08/23 19:00', function (event) {
    var $this = jQuery(this).html(event.strftime('' +
        '<div style="margin: 0px; padding: 0px; width: 85px;" class="time-entry days"><span>%-D</span> Dia(s)</div> ' +
        '<div style="margin-top: 50px; padding: 0px; width: 85px;" class="time-entry hours"><span>%H</span> Hora(s)</div> ' +
        '<div style="margin-top: 50px; padding: 0px; width: 85px;" class="time-entry minutes"><span>%M</span> Minuto(s)</div> ' +
        '<div style="margin-top: 50px; padding: 0px; width: 85px;" class="time-entry seconds"><span>%S</span> Segundo(s)</div> '));
});

