document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  const btnShowLogin = document.getElementById('btnShowLogin');
  const btnShowRegister = document.getElementById('btnShowRegister');
  const regCancelar = document.getElementById('regCancelar');

  const showRegisterOnLoad = document.body?.dataset?.openRegister === '1';
  if (showRegisterOnLoad) {
    loginForm.style.display = 'none';
    registerForm.style.display = '';
  }

  btnShowLogin.onclick = () => {
    loginForm.style.display = '';
    registerForm.style.display = 'none';
  };

  btnShowRegister.onclick = () => {
    loginForm.style.display = 'none';
    registerForm.style.display = '';
  };

  regCancelar.onclick = () => {
    loginForm.style.display = '';
    registerForm.style.display = 'none';
  };
});
