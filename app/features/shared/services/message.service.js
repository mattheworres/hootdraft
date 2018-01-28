class MessageService {
  constructor(toastr) {
    this.toastr = toastr;
  }

  showSuccess(message, title) {
    if (title == null) { title = 'Success!'; }
    this.toastr.success(message, title);
  }

  showError(message, title) {
    if (title == null) { title = 'Error'; }
    this.toastr.error(message, title);
  }

  showInfo(message, title) {
    if (title == null) { title = 'Information'; }
    this.toastr.info(message, title);
  }

  showWarning(message, title) {
    if (title == null) { title = 'Warning'; }
    this.toastr.warning(message, title);
  }

  closeToasts(toast) {
    if (typeof toast === 'undefined') {
      this.toastr.clear();
    } else {
      this.toastr.clear(toast);
    }
  }
}

MessageService.$inject = [
  'toastr'
];

angular.module('phpdraft').service('messageService', MessageService);
