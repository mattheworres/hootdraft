const YEAR_IN_SECONDS = 31536000;
const WEEK_IN_SECONDS = 604800;
const DAY_IN_SECONDS = 86400;
const HOUR_IN_SECONDS = 3600;
const MINUTE_IN_SECONDS = 60;

angular.module('phpdraft.shared').filter('preciseHumanizedSeconds', () =>
  function (startingSeconds) {
    let wordsContainer = {
      words: '',
      remainingSeconds: startingSeconds,
    };

    const humanizeRemainingSeconds = (container, secondsBucketAmount, multipleLabel, singularLabel) => {
      if (container.remainingSeconds > secondsBucketAmount) {
        const secondsForBucket = parseInt(container.remainingSeconds / secondsBucketAmount, 10);

        if (secondsForBucket > 0) {
          container.remainingSeconds -= (secondsForBucket * secondsBucketAmount);
          const label = secondsForBucket > 1 ? multipleLabel : singularLabel;
          container.words = `${container.words} ${secondsForBucket} ${label}, `;
        }
      }

      return container;
    };

    wordsContainer = humanizeRemainingSeconds(wordsContainer, YEAR_IN_SECONDS, 'years', 'year');
    wordsContainer = humanizeRemainingSeconds(wordsContainer, WEEK_IN_SECONDS, 'weeks', 'week');
    wordsContainer = humanizeRemainingSeconds(wordsContainer, DAY_IN_SECONDS, 'days', 'day');
    wordsContainer = humanizeRemainingSeconds(wordsContainer, HOUR_IN_SECONDS, 'hours', 'hour');
    wordsContainer = humanizeRemainingSeconds(wordsContainer, MINUTE_IN_SECONDS, 'minutes', 'minute');

    const label = wordsContainer.remainingSeconds > 1 ? 'seconds' : 'second';

    return `${wordsContainer.words}${wordsContainer.remainingSeconds} ${label}`;
  }
);
