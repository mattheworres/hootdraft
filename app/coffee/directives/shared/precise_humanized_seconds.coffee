angular.module("app").filter 'preciseHumanizedSeconds', () ->
    return (seconds) ->
        words = ''

        if seconds > 31536000
          years = parseInt(seconds / 31536000, 10)
          if years > 0
            seconds = seconds - (years * 31536000)
            label = if years > 1 then 'years' else 'year'
            words = "#{words} #{years} #{label}, "

        if seconds > 604800
          weeks = parseInt(seconds / 604800, 10)
          if weeks > 0
            seconds = seconds - (weeks * 604800)
            label = if weeks > 1 then 'weeks' else 'week'
            words = "#{words} #{weeks} #{label}, "

        if seconds > 86400
          days = parseInt(seconds / 86400, 10)
          if days > 0
            seconds = seconds - (days * 86400)
            label = if days > 1 then 'days' else 'day'
            words = "#{words} #{days} #{label}, "

        if seconds > 3600
          hours = parseInt(seconds / 3600, 10)
          if hours > 0
            seconds = seconds - (hours * 3600)
            label = if hours > 1 then 'hours' else 'hour'
            words = "#{words} #{hours} #{label}, "

        if seconds > 60
          minutes = parseInt(seconds / 60, 10)
          if minutes > 0
            seconds = seconds - (minutes * 60)
            label = if minutes > 1 then 'minutes' else 'minute'
            words = "#{words} #{minutes} #{label}, "

        label = if seconds > 1 then 'seconds' else 'second'
        words = "#{words}#{seconds} #{label}"

        words