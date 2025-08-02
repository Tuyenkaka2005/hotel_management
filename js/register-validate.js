document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('register-form');
    const errorDiv = document.getElementById('register-error');
    if (!form) return;
    const fields = ['fullname', 'username', 'password', 'email', 'phone'];
    let duplicateErrors = { username: '', email: '', phone: '' };
    function clearErrorStyles() {
        fields.forEach(f => {
            const input = form[f];
            if (input) input.style.borderColor = '';
        });
    }
    function showDuplicateError(field, msg) {
        duplicateErrors[field] = msg;
        const input = form[field];
        if (input) input.style.borderColor = msg ? '#d8000c' : '';
        updateErrorDiv();
    }
    function updateErrorDiv() {
        let errors = Object.values(duplicateErrors).filter(Boolean);
        if (errors.length > 0) {
            errorDiv.innerHTML = errors.map(e => `<div>${e}</div>`).join('');
            errorDiv.style.display = 'block';
        } else {
            errorDiv.innerHTML = '';
            errorDiv.style.display = 'none';
        }
    }
    // AJAX check duplicate
    ['username','email','phone'].forEach(field => {
        const input = form[field];
        if (input) {
            input.addEventListener('blur', function() {
                const value = input.value.trim();
                if (!value) {
                    showDuplicateError(field, '');
                    return;
                }
                fetch(`check-duplicate.php?field=${field}&value=${encodeURIComponent(value)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.exists) {
                            let msg = '';
                            if (field === 'username') msg = 'Username already exists!';
                            if (field === 'email') msg = 'Email already exists!';
                            if (field === 'phone') msg = 'Phone number already exists!';
                            showDuplicateError(field, msg);
                        } else {
                            showDuplicateError(field, '');
                        }
                    });
            });
        }
    });
    fields.forEach(f => {
        const input = form[f];
        if (input) {
            input.addEventListener('input', () => {
                input.style.borderColor = '';
                errorDiv.style.display = 'none';
                if (duplicateErrors[f]) {
                    showDuplicateError(f, '');
                }
            });
        }
    });
    form.addEventListener('submit', function(e) {
        clearErrorStyles();
        errorDiv.style.display = 'none';
        errorDiv.innerHTML = '';
        let errors = [];
        const fullname = form.fullname.value.trim();
        const username = form.username.value.trim();
        const password = form.password.value;
        const email = form.email.value.trim();
        const phone = form.phone.value.trim();
        if (!fullname) {
            errors.push('Please enter your full name!');
            form.fullname.style.borderColor = '#d8000c';
        }
        if (!username) {
            errors.push('Please enter your username!');
            form.username.style.borderColor = '#d8000c';
        }
        if (!password) {
            errors.push('Please enter your password!');
            form.password.style.borderColor = '#d8000c';
        } else if (password.length < 6) {
            errors.push('Password must be at least 6 characters!');
            form.password.style.borderColor = '#d8000c';
        }
        if (!email) {
            errors.push('Please enter your email!');
            form.email.style.borderColor = '#d8000c';
        } else if (!/^\S+@\S+\.\S+$/.test(email)) {
            errors.push('Email is not valid!');
            form.email.style.borderColor = '#d8000c';
        }
        if (phone && !/^[0-9\-\+ ]{8,15}$/.test(phone)) {
            errors.push('Phone number is not valid!');
            form.phone.style.borderColor = '#d8000c';
        }
        // Nếu có lỗi trùng lặp thì không submit
        if (Object.values(duplicateErrors).some(Boolean)) {
            errors = errors.concat(Object.values(duplicateErrors).filter(Boolean));
        }
        if (errors.length > 0) {
            e.preventDefault();
            errorDiv.innerHTML = errors.map(e => `<div>${e}</div>`).join('');
            errorDiv.style.display = 'block';
            errorDiv.scrollIntoView({behavior:'smooth', block:'center'});
        }
    });
}); 