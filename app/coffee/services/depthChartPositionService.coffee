class DepthChartPositionService extends AngularService
  @register 'depthChartPositionService'
  @inject 'lodash'

  createPositionsBySport: (draft, sportPositions, callback) ->
    if draft.draft_id is not null
      draft.using_depth_charts = true

    draft.depthChartPositions.length = 0

    for abbreviation, position of sportPositions
      newPosition = @_generateNewDepthChartPosition(abbreviation)
      draft.depthChartPositions.push newPosition

    draft.depthChartPositions.push @_generateNewDepthChartPosition('BN')

    if typeof callback == "function"
      callback()

  getDepthChartPositionValidity: (draft) ->
    depthChartsUnique = true

    if draft?.using_depth_charts
      for position, index in draft.depthChartPositions
        foundIndex = @lodash.findIndex(draft.depthChartPositions, { 'position': position.position })
        if foundIndex != index and foundIndex != -1
          depthChartsUnique = false
    else
      depthChartsUnique = true

    return depthChartsUnique

  addDepthChartPosition: (draft) ->
    draft.depthChartPositions.push @_generateNewDepthChartPosition()

  deleteDepthChartPosition: (draft, index) ->
    draft.depthChartPositions.splice index, 1

  calculateRoundsFromPositions: (draft) ->
    hasNonstandardPositions = false

    if draft?.using_depth_charts
      draft.draft_rounds = 0

      for position in draft.depthChartPositions
        positionSlots = parseInt position.slots, 10
        if positionSlots > 0
          draft.draft_rounds += positionSlots
        lowerPosition = position.position?.toLowerCase()

        if lowerPosition is 'dl'
          hasNonstandardPositions = draft.draft_sport is not 'NFLE'
        else if lowerPosition is 'ir' or lowerPosition is 'ir+' or lowerPosition is 'na'
          hasNonstandardPositions = true

    return hasNonstandardPositions

  _generateNewDepthChartPosition: (positionAbbreviation) ->
    return {
      position: positionAbbreviation
      slots: 1
    }