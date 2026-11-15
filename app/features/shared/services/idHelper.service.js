class IdHelperService {
  //API ids are numeric strings or numbers, route ids are strings. Two missing ids count as the same id.
  isSame(a, b) {
    const aMissing = this._isMissing(a);
    const bMissing = this._isMissing(b);

    if (aMissing || bMissing) {
      return aMissing && bMissing;
    }

    return Number(a) === Number(b);
  }

  _isMissing(id) {
    return angular.isUndefined(id) || id === null || id === '';
  }
}

angular.module('phpdraft.shared').service('idHelperService', IdHelperService);
