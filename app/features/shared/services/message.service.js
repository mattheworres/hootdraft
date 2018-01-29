class MessageService {
  constructor(toastr) {
    this.toastr = toastr;
  }

  showSuccess(message, title) {
    const messageTitle = title === null ? 'Success!' : title;
    this.toastr.success(message, messageTitle);
  }

  showError(message, title) {
    const messageTitle = title === null ? 'Error' : title;
    this.toastr.error(message, messageTitle);
  }

  showInfo(message, title) {
    const messageTitle = title === null ? 'Information' : title;
    this.toastr.info(message, messageTitle);
  }

  showWarning(message, title) {
    const messageTitle = title === null ? 'Warning' : title;
    this.toastr.warning(message, messageTitle);
  }

  closeToasts(toast) {
    if (angular.isUndefined(toast)) {
      this.toastr.clear();
    } else {
      this.toastr.clear(toast);
    }
  }
}

MessageService.$inject = [
  'toastr',
];

angular.module('phpdraft.shared').service('messageService', MessageService);
