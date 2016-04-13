class PickService extends AngularService
  @register 'pickService'
  @inject '$uibModal', '$q', 'api'

  checkForExistingPicks: (draft_id, currentPick) ->
    existingPickDeferral = @$q.defer()

    alreadyDraftedSuccess = (data) =>
      if data.possibleMatchExists == false
        existingPickDeferral.resolve(
          pickIsNotDuplicate: true
        )
      else
        duplicatePickModalResult = @showDuplicatePickModal(currentPick, data.matches)

        duplicatePickSuccess = (response) =>
          existingPickDeferral.resolve(
            pickIsNotDuplicate: response.pickIsNotDuplicate
          )
        duplicatePickError = (response) =>
          existingPickDeferral.reject(
            error: "Unable to confirm if duplicate player is intended"
          )

        duplicatePickModalResult.promise.then duplicatePickSuccess, duplicatePickError

    errorHandler = (response) =>
      @messageService.showError "Unable to search for player already drafted"
      existingPickDeferral.reject(
        data: response
        status: response.status
      )

    @api.Pick.alreadyDrafted({draft_id: draft_id, first_name: currentPick.first_name, last_name: currentPick.last_name}, alreadyDraftedSuccess, errorHandler)

    return existingPickDeferral

  showDuplicatePickModal: (currentPick, matches) ->
    duplicatePicKModalDeferral = @$q.defer()

    @modalInstance = @$uibModal.open
      templateUrl: 'app/templates/modals/duplicate_pick.html',
      controller: 'DuplicatePickModalController',
      controllerAs: 'modalCtrl',
      resolve:
        duplicateMatches: =>
          matches
        currentPick: =>
          currentPick

    @modalInstance.result.then (clickedYes) =>
      @modalInstance.dismiss('cancel')

      duplicatePicKModalDeferral.resolve(
        pickIsNotDuplicate: clickedYes
      )

    return duplicatePicKModalDeferral

  closeModal: ->
    @modalInstance?.close?()