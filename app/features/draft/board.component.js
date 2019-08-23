const LOAD_PREVIOUS_DISPLAY = 'load_previous_display';
const LOAD_CURRENT_DISPLAY = 'load_current_display';

class BoardController {
  constructor(
    $scope,
    $routeParams,
    $loading,
    subscriptionKeys,
    api,
    messageService,
    draftService,
    pathHelperService,
    lodash
  ) {
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.$loading = $loading;
    this.subscriptionKeys = subscriptionKeys;
    this.api = api;
    this.messageService = messageService;
    this.draftService = draftService;
    this.pathHelperService = pathHelperService;
    this.lodash = lodash;
  }

  $onInit() {
    this.initialBoardLoaded = false;
    this.boardLoading = true;
    this.timerEnabled = false;
    this.timerUp = false;
    this.calculatedBoardWidth = '100%';
    this.currentDraftCounter = 0;

    this.draftService.getDraft().then(draft => {
      this.draft = draft;

      this.draftStatus = this.draftService.getStatus();

      this.onDraftCounterChanged(this.draft, this.draftStatus);

      this.deregister = this.$scope
        .$on(this.subscriptionKeys.draftCounterHasChanged, (event, args) => {
          const {draft, status} = args;

          this.onDraftCounterChanged(draft, status);
        })
        .bind(this);
    });
  }

  onDraftCounterChanged(draft, status) {
    if (angular.isDefined(this.draft)) {
      //Stash the current counter so we can run a diff to get updated picks
      this.currentDraftCounter = this.draft.draft_counter;
      this.lodash.merge(this.draft, draft);
    } else {
      this.draft = draft;
    }

    this.lodash.merge(this.draftStatus, status);

    if (this.draft.setting_up === true) {
      this.pageError = true;
      this.pathHelperService.sendToPreviousPath();
      this.messageService.showWarning('Draft is still setting up');
      this.deregister();
    } else if (
      this.draft.in_progress === true ||
      this.draft.complete === true
    ) {
      if (this.initialBoardLoaded) {
        this.loadUpdatedData(this.draft.draft_id);
      } else {
        this.loadInitialBoard(this.draft.draft_id);
      }
    }
  }

  $onDestroy() {
    this.deregister();
  }

  calculateBoardWidth(numberOfManagers) {
    //See width in board.less for the magic numbers below
    //managers * pick width + round number width + left and right borders
    const numberOfPixels = numberOfManagers * 175 + 50 + 4;

    this.calculatedBoardWidth = `${numberOfPixels}px`;
  }

  loadInitialBoard(draftId) {
    this.initialBoardLoaded = true;
    this.boardLoading = true;

    const initialSuccess = data => {
      this.pickRounds = data.allPicks;
      this.boardLoading = false;
      const numberOfManagers = this.pickRounds[0].length;
      this.calculateBoardWidth(numberOfManagers);
    };

    const errorHandler = () => {
      this.boardLoading = false;
      this.messageService.showError('Unable to load draft board');
    };

    if (this.draftStatus.valid && !this.draftStatus.locked) {
      this.api.Pick.getAll({draft_id: draftId}, initialSuccess, errorHandler); // eslint-disable-line camelcase
      this.loadCurrentAndNextPicks(draftId);
      this.loadTimeRemaining(draftId);
    }
  }

  loadUpdatedData(draftId) {
    const updatedSuccess = data => {
      const counterChanged = this.currentDraftCounter !== data.draft_counter;

      this.currentDraftCounter = data.draft_counter;

      if (!counterChanged) {
        return;
      }

      //Rather than hitting these separately, we've been handed them already, so update them.
      //@loadCurrentAndNextPicks(draftId)
      this.currentPick = data.current_pick;
      this.previousPick = data.previous_pick;
      this.hasPreviousPick =
        this.previousPick !== null && angular.isDefined(this.previousPick);

      this.updateBoardPick(data.current_pick);
      this.updateBoardPick(data.previous_pick);

      if (data.updated_picks === null || data.updated_picks.length === 0) {
        return;
      }

      for (const updatedPick of Array.from(data.updated_picks)) {
        if (updatedPick.player_pick >= this.currentPick.player_pick) {
          this.resetTimer();
        }
        this.updateBoardPick(updatedPick);
      }

      this.$loading.finish(LOAD_CURRENT_DISPLAY);
      this.$loading.finish(LOAD_PREVIOUS_DISPLAY);
    };

    const errorHandler = () => {
      this.$loading.finish(LOAD_CURRENT_DISPLAY);
      this.$loading.finish(LOAD_PREVIOUS_DISPLAY);
      this.messageService.showError('Unable to get up to date draft picks');
    };

    if (this.draftStatus.valid && !this.draftStatus.locked) {
      this.$loading.start(LOAD_CURRENT_DISPLAY);
      this.$loading.start(LOAD_PREVIOUS_DISPLAY);
      this.api.Pick.getUpdated(
        {draft_id: draftId, pick_counter: this.currentDraftCounter},
        updatedSuccess,
        errorHandler
      ); // eslint-disable-line camelcase
      this.loadTimeRemaining(draftId);
    }
  }

  updateBoardPick(updatedPick) {
    if (angular.isUndefined(this.pickRounds)) {
      return;
    }

    const updatedPickRoundIndex = updatedPick.player_round - 1;
    const round = this.pickRounds[updatedPickRoundIndex];

    for (let index = 0; index < round.length; index++) {
      const roundXPick = round[index];
      if (roundXPick.player_id === updatedPick.player_id) {
        this.pickRounds[updatedPickRoundIndex][index] = updatedPick;
      }
    }
  }

  loadCurrentAndNextPicks(draftId) {
    const currentSuccess = data => {
      this.$loading.finish(LOAD_CURRENT_DISPLAY);
      this.currentPick = data[0];
      if (this.initialBoardLoaded) {
        this.updateBoardPick(data[0]);
      }
    };

    const lastSuccess = data => {
      this.$loading.finish(LOAD_PREVIOUS_DISPLAY);
      this.previousPick = data[0];
      this.hasPreviousPick =
        this.previousPick !== null && angular.isDefined(this.previousPick);
    };

    const errorHandler = () => {
      this.$loading.finish(LOAD_CURRENT_DISPLAY);
      this.$loading.finish(LOAD_PREVIOUS_DISPLAY);
      this.messageService.showError('Unable to get next and/or current picks');
    };

    if (this.draftStatus.valid && !this.draftStatus.locked) {
      this.$loading.start(LOAD_CURRENT_DISPLAY);
      this.$loading.start(LOAD_PREVIOUS_DISPLAY);

      this.api.Pick.getNext(
        {draft_id: draftId, amount: 1},
        currentSuccess,
        errorHandler
      ); // eslint-disable-line camelcase
      this.api.Pick.getLast(
        {draft_id: draftId, amount: 1},
        lastSuccess,
        errorHandler
      ); // eslint-disable-line camelcase
    }
  }

  resetTimer() {
    this.timerClockStopHandler();
    this.loadTimeRemaining(this.$routeParams.draft_id);
  }

  timerClockStopHandler() {
    this.timerUp = true;
  }

  //TODO: Wait 1s, then play sounds here
  //TODO For sound, ensure we're kicked off by clock and not by pick being made

  loadTimeRemaining(draftId) {
    const timersSuccess = data => {
      this.timerEnabled = data.timer_enabled;

      if (this.timerEnabled) {
        const seconds = parseInt(data.seconds_remaining, 10);
        this.timerUp = seconds === 0;
        if (seconds > 0) {
          //These methods are flipclock methods
          this.$scope.setTime(seconds);
          this.$scope.start();
        }
      }
    };

    const errorHandler = () =>
      this.messageService.showError('Unable to load remaining pick time');

    if (
      this.draftStatus.valid &&
      !this.draftStatus.locked &&
      this.draft.in_progress === true
    ) {
      this.api.Draft.getTimeRemaining(
        {draft_id: draftId},
        timersSuccess,
        errorHandler
      ); // eslint-disable-line camelcase
    } else if (angular.isFunction(this.deregister)) {
      this.deregister();
    }
  }
}

BoardController.$inject = [
  '$scope',
  '$routeParams',
  '$loading',
  'subscriptionKeys',
  'api',
  'messageService',
  'draftService',
  'pathHelperService',
  'lodash',
];

angular.module('phpdraft.draft').component('phpdBoard', {
  controller: BoardController,
  templateUrl: 'app/features/draft/board.component.html',
});
