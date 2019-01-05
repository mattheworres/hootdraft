const YEAR_IN_SECONDS = 31536000;
const WEEK_IN_SECONDS = 604800;
const DAY_IN_SECONDS = 86400;
const HOUR_IN_SECONDS = 3600;
const MINUTE_IN_SECONDS = 60;

angular.module('phpdraft.shared').filter('preciseHumanizedSeconds', () =>
  function (startingSeconds) {
    let label;
    let seconds = startingSeconds;
    let words = '';

    if (seconds > YEAR_IN_SECONDS) {
      const years = parseInt(seconds / YEAR_IN_SECONDS, 10);
      if (years > 0) {
        seconds -= (years * YEAR_IN_SECONDS);
        label = years > 1 ? 'years' : 'year';
        words = `${words} ${years} ${label}, `;
      }
    }

    if (seconds > WEEK_IN_SECONDS) {
      const weeks = parseInt(seconds / WEEK_IN_SECONDS, 10);
      if (weeks > 0) {
        seconds -= (weeks * WEEK_IN_SECONDS);
        label = weeks > 1 ? 'weeks' : 'week';
        words = `${words} ${weeks} ${label}, `;
      }
    }

    if (seconds > DAY_IN_SECONDS) {
      const days = parseInt(seconds / DAY_IN_SECONDS, 10);
      if (days > 0) {
        seconds -= (days * DAY_IN_SECONDS);
        label = days > 1 ? 'days' : 'day';
        words = `${words} ${days} ${label}, `;
      }
    }

    if (seconds > HOUR_IN_SECONDS) {
      const hours = parseInt(seconds / HOUR_IN_SECONDS, 10);
      if (hours > 0) {
        seconds -= (hours * HOUR_IN_SECONDS);
        label = hours > 1 ? 'hours' : 'hour';
        words = `${words} ${hours} ${label}, `;
      }
    }

    if (seconds > MINUTE_IN_SECONDS) {
      const minutes = parseInt(seconds / MINUTE_IN_SECONDS, 10);
      if (minutes > 0) {
        seconds -= (minutes * MINUTE_IN_SECONDS);
        label = minutes > 1 ? 'minutes' : 'minute';
        words = `${words} ${minutes} ${label}, `;
      }
    }

    label = seconds > 1 ? 'seconds' : 'second';
    words = `${words}${seconds} ${label}`;

    return words;
  }
);
