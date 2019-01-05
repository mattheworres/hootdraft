class DepthChartPositionService {
  constructor(lodash) {
    this.lodash = lodash;
  }

  createPositionsBySport(draft, sportPositions, callback) {
    for (const abbreviation in sportPositions) {
      if (abbreviation && abbreviation.length > 0) {
        const newPosition = this._generateNewDepthChartPosition(abbreviation);
        draft.depthChartPositions.push(newPosition);
      }
    }

    draft.depthChartPositions.push(this._generateNewDepthChartPosition('BN'));

    if (angular.isFunction(callback)) {
      callback();
    }
  }

  getDepthChartPositionValidity(draft) {
    let depthChartsUnique = true;

    if ((draft !== null && draft.using_depth_charts)) {
      for (let index = 0; index < draft.depthChartPositions.length; index++) {
        const position = draft.depthChartPositions[index];
        const foundIndex = this.lodash.findIndex(draft.depthChartPositions, {'position': position.position});
        if ((foundIndex !== index) && (foundIndex !== -1)) {
          depthChartsUnique = false;
        }
      }
    } else {
      depthChartsUnique = true;
    }

    return depthChartsUnique;
  }

  addDepthChartPosition(depthChartPositions) {
    depthChartPositions.push(this._generateNewDepthChartPosition());
    return depthChartPositions;
  }

  deleteDepthChartPosition(depthChartPositions, index) {
    depthChartPositions.splice(index, 1);
    return depthChartPositions;
  }

  calculateRoundsFromPositions(draft) {
    let hasNonstandardPositions = false;

    if (draft !== null && draft.using_depth_charts) {
      draft.draft_rounds = 0; // eslint-disable-line camelcase

      for (const position of Array.from(draft.depthChartPositions)) {
        const positionSlots = parseInt(position.slots, 10);
        if (positionSlots > 0) {
          draft.draft_rounds += positionSlots; // eslint-disable-line camelcase
        }
        const lowerPosition = angular.isUndefined(position.position) ? '' : position.position.toLowerCase();

        if (lowerPosition === 'dl') {
          hasNonstandardPositions = draft.draft_sport === !'NFLE';
        } else if ((lowerPosition === 'ir') || (lowerPosition === 'ir+') || (lowerPosition === 'na') || (lowerPosition === 'n/a')) {
          hasNonstandardPositions = true;
        }
      }
    }

    return hasNonstandardPositions;
  }

  _generateNewDepthChartPosition(positionAbbreviation) {
    return {
      position: positionAbbreviation,
      slots: 1,
    };
  }
}

DepthChartPositionService.$inject = [
  'lodash',
];

angular.module('phpdraft.shared').service('depthChartPositionService', DepthChartPositionService);
