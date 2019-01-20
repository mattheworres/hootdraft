class PickService {
  constructor($uibModal, $q, api) {
    this.$uibModal = $uibModal;
    this.$q = $q;
    this.api = api;
  }

  checkForExistingPicks(draft_id, currentPick) {
    const existingPickDeferral = this.$q.defer();

    const alreadyDraftedSuccess = data => {
      if (data.possibleMatchExists === false) {
        existingPickDeferral.resolve({
          pickIsNotDuplicate: true,
        });

        return;
      }

      const duplicatePickModalResult = this.showDuplicatePickModal(currentPick, data.matches);

      const duplicatePickSuccess = response => {
        existingPickDeferral.resolve({
          pickIsNotDuplicate: response.pickIsNotDuplicate,
        });
      };

      const duplicatePickError = response => {
        existingPickDeferral.reject({
          pickIsNotDuplicate: response.pickIsNotDuplicate,
        });
      };

      duplicatePickModalResult.promise.then(duplicatePickSuccess, duplicatePickError);
    };

    const errorHandler = response => {
      this.messageService.showError('Unable to search for player already drafted');
      existingPickDeferral.reject({
        data: response,
        status: response.status,
      });
    };

    this.api.Pick.alreadyDrafted({draft_id, first_name: currentPick.first_name, last_name: currentPick.last_name}, alreadyDraftedSuccess, errorHandler);

    return existingPickDeferral;
  }

  showDuplicatePickModal(currentPick, matches) {
    const duplicatePickModalDeferral = this.$q.defer();

    this.modalInstance = this.$uibModal.open({
      template: `<phpd-duplicate-pick-modal
        current-pick="::$resolve.currentPick"
        duplicate-matches="::$resolve.duplicateMatches"
        dismiss="$dismiss()"
        close="$close()">
      </phpd-duplicate-pick-modal>`,
      controller: angular.noop,
      resolve: {
        currentPick: () => currentPick,
        duplicateMatches: () => matches,
      },
    });

    this.modalInstance.result.then(clickedYes => {
      this.modalInstance.dismiss('cancel');

      duplicatePickModalDeferral.resolve({
        pickIsNotDuplicate: clickedYes,
      });
    }, () => {
      duplicatePickModalDeferral.reject({pickIsNotDuplicate: false});
    });

    return duplicatePickModalDeferral;
  }

  closeModal() {
    if (angular.isObject(this.modalInstance) && angular.isFunction(this.modalInstance.close)) {
      this.modalInstance.close();
    }
  }

  determinePickSelected(pick) {
    const hasFirst = this._pickPropertyExists(pick.first_name);
    const hasLast = this._pickPropertyExists(pick.last_name);
    const hasTeam = this._pickPropertyExists(pick.team);
    const hasPosition = this._pickPropertyExists(pick.position);

    return hasFirst || hasLast || hasTeam || hasPosition;
  }

  _pickPropertyExists(property) {
    return property !== null && property.length > 0;
  }
}

PickService.$inject = [
  '$uibModal',
  '$q',
  'api',
];

angular.module('phpdraft.shared').service('pickService', PickService);
