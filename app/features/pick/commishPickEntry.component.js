class CommishPickEntryController {
  constructor($scope,
    $routeParams,
    messageService,
    pickService,
    api,
    limitToFilter,
    subscriptionKeys) {
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.messageService = messageService;
    this.pickService = pickService;
    this.api = api;
    this.limitToFilter = limitToFilter;
    this.subscriptionKeys = subscriptionKeys;

    this.selectPlayer = this.selectPlayer.bind(this);
    this._add = this._add.bind(this);
    this.manualEntryChange = this.manualEntryChange.bind(this);
  }

  $onInit() {
    this.manualEntry = false;
    this.playerSearchLoading = false;
    this.currentPick = {first_name: null, last_name: null, team: null, position: null};
  }

  $onChanges(changes) {
    const {teams, positions, currentPick} = changes;

    if (teams && teams.currentValue) {
      this.$scope.teams = teams.currentValue;
    }

    if (positions && positions.currentValue) {
      this.$scope.positions = positions.currentValue;
    }

    if (currentPick && currentPick.isFirstChange()) {
      this.currentPick = angular.merge({}, this.currentPick, currentPick.currentValue);
    }

    if (currentPick && currentPick.isFirstChange() === false) {
      const localCurrentPick = currentPick.currentValue;
      this.isFirstPick = (angular.isDefined(localCurrentPick) ? localCurrentPick.player_pick : 0) === '1';

      //If manual entry, we need to make sure to properly update "selected" on the current pick so the display acts accordingly
      if (this.manualEntry) {
        //If we have a first AND last name, go ahead and show this as a pick. May not have position coloring, but thats OK.
        const hasFirst = (localCurrentPick.first_name !== null) && (localCurrentPick.first_name.length > 0);
        const hasLast = (localCurrentPick.last_name !== null) && (localCurrentPick.last_name.length > 0);
        const hasTeam = (localCurrentPick.team !== null) && (localCurrentPick.team.length > 0);
        const hasPosition = (localCurrentPick.position !== null) && (localCurrentPick.position.length > 0);

        this.currentPick.selected = hasFirst || hasLast || hasTeam || hasPosition;
      }
    }
  }

  manualEntryChange() {
    if (this.manualEntry) {
      //If we have a first AND last name, go ahead and show this as a pick. May not have position coloring, but thats OK.
      const hasFirst = (angular.isDefined(this.currentPick.first_name)) && (this.currentPick.first_name.length > 0);
      const hasLast = (angular.isDefined(this.currentPick.last_name)) && (this.currentPick.last_name.length > 0);
      const hasTeam = (angular.isDefined(this.currentPick.team)) && (this.currentPick.team.length > 0);
      const hasPosition = (angular.isDefined(this.currentPick.position)) && (this.currentPick.position.length > 0);

      this.currentPick.selected = hasFirst || hasLast || hasTeam || hasPosition;
    }
  }

  buttonClicked() {
    if (!this.formIsInvalid()) {
      this._add();
    }
  }

  formIsInvalid() {
    return this.editInProgress || !this.form.$valid;
  }

  _add() {
    //this._updateStateUpstream(true, this.manualEntry, this.currentPick);
    this.editInProgress = true;

    //If the user has chosen to perform manual entry, we just automatically run the check again,
    //For edit contexts, if its the same player, just let it go thru.
    if (this.manualEntry && !this._pickIsTheSame()) {
      const duplicateCheckSuccess = response => {
        if (response.pickIsNotDuplicate === false) {
          this.wipePick();
          this.editInProgress = false;
          this.messageService.showInfo('Pick was reset - go ahead and enter another player.');
        } else if (angular.isFunction(this.pickAction)) {
          this.pickAction();
          this.manualEntry = false;
        }
      };

      const duplicateCheckFailure = response => {
        this.editInProgress = false;
        if (response.pickIsNotDuplicate) {
          this.messageService.showError('Unable to enter pick - error while checking for duplicates.');
          return;
        }

        this.wipePick();
        this.messageService.showInfo('Pick was reset - go ahead and enter another player.');
      };

      const duplicateResult = this.pickService.checkForExistingPicks(this.$routeParams.draft_id, this.currentPick);

      duplicateResult.promise.then(duplicateCheckSuccess, duplicateCheckFailure);
    } else if (angular.isFunction(this.pickAction)) {
      this.pickAction();
    }
  }

  proPlayerSearch(searchTerm) {
    return this.api.Pick.searchProPlayers({league: this.draft.draft_sport, searchTerm}).$promise.then(data => this.limitToFilter(data.players, 10)).catch(() => {
      this.messageService.closeToasts();
      this.messageService.showError('Unable to search pro players');
    });
  }

  selectPlayer(item) {
    item.selected = true;
    //Want to keep data about pick (round, pick #) as well as add player name, position, team, so merge not assignment:
    this.currentPick = angular.merge({}, this.currentPick, item);

    delete this.playerSearch;
    this.playerSearch = '';

    //For edit contexts, if its the same player, just let it go thru.
    //Potentially fixes #12: https://github.com/mattheworres/phpdraft/issues/12
    //Ensure that the current pick is not in the process of being loaded.
    //Select player may be getting erroneously fired while the current pick is being reloaded.
    if (!this._pickIsTheSame()) {
      //Perform an eager API call to ensure autocomplete player is not a duplicate
      const duplicateCheckSuccess = response => {
        //If there were matches and the user wanted to wipe the pick, then do that. Otherwise, do nothing
        if (response.pickIsNotDuplicate === false) {
          this.wipePick();
        }
      };

      const duplicateCheckFailure = response => {
        if (response.pickIsNotDuplicate) {
          this.messageService.showError('Unable to select pick - error while checking for duplicates.');
          return;
        }

        this.messageService.showInfo('Pick was reset - go ahead and enter another player.');
        this.wipePick();
      };

      const duplicateResult = this.pickService.checkForExistingPicks(this.$routeParams.draft_id, this.currentPick);

      duplicateResult.promise.then(duplicateCheckSuccess, duplicateCheckFailure);
    }
  }

  wipePick() {
    this.manualEntry = false;
    this.currentPick = this.pristinePick;
  }

  _pickIsTheSame() {
    const firstIsSame = this.currentPick.first_name === this.pristinePick.first_name;
    const lastIsSame = this.currentPick.last_name === this.pristinePick.last_name;
    const teamIsSame = this.currentPick.team === this.pristinePick.team;
    const positionIsSame = this.currentPick.position === this.pristinePick.position;

    return firstIsSame && lastIsSame && teamIsSame && positionIsSame;
  }
}

CommishPickEntryController.$inject = [
  '$scope',
  '$routeParams',
  'messageService',
  'pickService',
  'api',
  'limitToFilter',
  'subscriptionKeys',
];

angular.module('phpdraft.pick').component('phpdCommishPickEntry', {
  controller: CommishPickEntryController,
  templateUrl: 'app/features/pick/commishPickEntry.component.html',
  bindings: {
    draft: '<',
    currentPick: '=',
    isFirstPick: '=',
    pristinePick: '<',
    positions: '<',
    teams: '<',
    editInProgress: '=',
    pickAction: '&',
    pickActionText: '@',
    pickIcon: '@',
  },
});
