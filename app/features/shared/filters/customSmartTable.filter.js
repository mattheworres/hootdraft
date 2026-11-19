//From the smart-table docs: http://lorenzofox3.github.io/smart-table-website/#filter-custom-plugin
angular.module('phpdraft.shared').filter('customSmartTableFilter', ['$filter', $filter => {
  const filterFilter = $filter('filter');
  const standardComparator = (obj, text) => {
    const newText = (`${text}`).toLowerCase();
    return (`${obj}`).toLowerCase().indexOf(newText) > -1;
  };

  return (array, expression) => {
    function customComparator(actual, expected) {

      const isBeforeActivated = expected.before;
      const isAfterActivated = expected.after;
      const isLower = expected.lower;
      const isHigher = expected.higher;
      let higherLimit;
      let lowerLimit;
      let itemDate;
      let queryDate;

      if (angular.isObject(expected)) {
        //exact match
        if (expected.distinct) {
          // W-37: the Commissioner filter compares numeric IDs, and numbers have no toLowerCase(), so compare
          // as strings. This is a quick fix for AngularJS only. The new client must use typed IDs and compare
          // them with `===`, with no string conversion.
          if (!actual || `${actual}`.toLowerCase() !== `${expected.distinct}`.toLowerCase()) {
            return false;
          }

          return true;
        }

        //matchAny
        if (expected.matchAny) {
          if (expected.matchAny.all) {
            return true;
          }

          if (!actual) {
            return false;
          }

          for (let i = 0; i < expected.matchAny.items.length; i++) {
            if (actual.toLowerCase() === expected.matchAny.items[i].toLowerCase()) {
              return true;
            }
          }

          return false;
        }

        //date range
        if (expected.before || expected.after) {
          try {
            if (isBeforeActivated) {
              higherLimit = expected.before;

              itemDate = new Date(actual);
              queryDate = new Date(higherLimit);

              if (itemDate > queryDate) {
                return false;
              }
            }

            if (isAfterActivated) {
              lowerLimit = expected.after;


              itemDate = new Date(actual);
              queryDate = new Date(lowerLimit);

              if (itemDate < queryDate) {
                return false;
              }
            }

            return true;
          } catch (e) {
            return false;
          }

        } else if (isLower || isHigher) {
          //number range
          if (isLower) {
            higherLimit = expected.lower;

            if (actual > higherLimit) {
              return false;
            }
          }

          if (isHigher) {
            lowerLimit = expected.higher;
            if (actual < lowerLimit) {
              return false;
            }
          }

          return true;
        }
        //etc

        return true;

      }
      return standardComparator(actual, expected);
    }

    const output = filterFilter(array, expression, customComparator);
    return output;
  };
}]);
