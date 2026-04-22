document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  const btnShowLogin = document.getElementById('btnShowLogin');
  const btnShowRegister = document.getElementById('btnShowRegister');
  const regCancelar = document.getElementById('regCancelar');

  btnShowLogin.onclick = () => { loginForm.style.display = ''; registerForm.style.display = 'none'; };
  btnShowRegister.onclick = () => { loginForm.style.display = 'none'; registerForm.style.display = ''; };

  loginForm.onsubmit = async (e) => {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value.trim();
    const senha = document.getElementById('loginSenha').value;
    document.getElementById('loginMsg').textContent = '';
    try {
      const res = await fetch('/cantina/login.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: JSON.stringify({ email, senha })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Falha no login');
      localStorage.setItem('user', JSON.stringify(data.user || {}));
      if (data.user && data.user.tipo === 'admin') location.href = 'admin.html';
      else location.href = 'index.html';
    } catch (err) {
      document.getElementById('loginMsg').textContent = err.message;
    }
  };


  regCancelar.onclick = () => { loginForm.style.display = ''; registerForm.style.display = 'none'; };
});