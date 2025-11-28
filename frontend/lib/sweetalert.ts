import Swal from 'sweetalert2';

// Success alert
export const showSuccess = (title: string, message?: string) => {
  return Swal.fire({
    icon: 'success',
    title,
    text: message,
    confirmButtonColor: '#2563eb',
    timer: 2000,
    timerProgressBar: true,
  });
};

// Error alert
export const showError = (title: string, message?: string) => {
  return Swal.fire({
    icon: 'error',
    title,
    text: message,
    confirmButtonColor: '#dc2626',
  });
};

// Warning alert
export const showWarning = (title: string, message?: string) => {
  return Swal.fire({
    icon: 'warning',
    title,
    text: message,
    confirmButtonColor: '#f59e0b',
  });
};

// Info alert
export const showInfo = (title: string, message?: string) => {
  return Swal.fire({
    icon: 'info',
    title,
    text: message,
    confirmButtonColor: '#3b82f6',
  });
};

// Confirmation dialog
export const showConfirm = (
  title: string,
  message: string,
  confirmText: string = 'Yes',
  cancelText: string = 'Cancel'
) => {
  return Swal.fire({
    title,
    text: message,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#6b7280',
    confirmButtonText: confirmText,
    cancelButtonText: cancelText,
  });
};

// Loading alert
export const showLoading = (title: string = 'Loading...') => {
  return Swal.fire({
    title,
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });
};

// Close any open alert
export const closeAlert = () => {
  Swal.close();
};

