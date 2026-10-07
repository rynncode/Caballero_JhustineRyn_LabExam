/* Client-side helpers. The server re-checks everything, so this is only for faster feedback. */
(function () {
    'use strict';

    var NAME_RE = /^[\p{L}][\p{L}\s'\-.]*$/u;
    var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    var rules = {
        first_name: function (v) { return nameRule(v, 'First name'); },
        last_name: function (v) { return nameRule(v, 'Last name'); },
        email: function (v) {
            if (!v) return 'Email is required.';
            if (!EMAIL_RE.test(v)) return 'Enter a valid email address, like name@example.com.';
            return '';
        }
    };

    function nameRule(v, label) {
        if (!v) return label + ' is required.';
        if (v.length < 2 || v.length > 50) return label + ' must be 2 to 50 characters.';
        if (!NAME_RE.test(v)) return label + ' can only contain letters, spaces, hyphens and apostrophes.';
        return '';
    }

    function passwordRule(v, isRegister) {
        if (!v) return 'Password is required.';
        if (!isRegister) return '';
        if (v.length < 8) return 'Password must be at least 8 characters.';
        if (!/[a-z]/.test(v) || !/[A-Z]/.test(v) || !/\d/.test(v)) {
            return 'Password needs an uppercase letter, a lowercase letter and a number.';
        }
        return '';
    }

    function setError(form, name, message) {
        var slot = form.querySelector('[data-error-for="' + name + '"]');
        if (!slot) return;
        slot.textContent = message;
        var field = slot.closest('.field');
        if (field) field.classList.toggle('has-error', !!message);
    }

    function value(form, name) {
        var el = form.elements[name];
        return el ? el.value : '';
    }

    function validate(form, mode) {
        var isRegister = mode === 'register';
        var checks = {};

        checks.email = rules.email(value(form, 'email').trim());
        var pw = value(form, 'password');
        checks.password = passwordRule(pw, isRegister);

        if (isRegister) {
            checks.first_name = rules.first_name(value(form, 'first_name').trim());
            checks.last_name = rules.last_name(value(form, 'last_name').trim());

            var confirm = value(form, 'confirm_password');
            if (!confirm) checks.confirm_password = 'Please enter your password again.';
            else if (!checks.password && pw !== confirm) checks.confirm_password = 'Passwords do not match.';
            else checks.confirm_password = '';

            checks.terms = form.elements.terms.checked ? '' : 'You must agree to the Terms and Conditions.';
        }

        var firstBad = null;
        Object.keys(checks).forEach(function (name) {
            setError(form, name, checks[name]);
            if (checks[name] && !firstBad) firstBad = name;
        });
        return firstBad;
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        var mode = form.getAttribute('data-validate');

        form.addEventListener('submit', function (e) {
            var bad = validate(form, mode);
            if (bad) {
                e.preventDefault();
                var el = form.elements[bad];
                if (el && el.focus) el.focus();
            }
        });

        // Clear an error as soon as the person starts fixing it.
        form.addEventListener('input', function (e) {
            if (e.target.name) setError(form, e.target.name, '');
        });
        form.addEventListener('change', function (e) {
            if (e.target.name === 'terms') setError(form, 'terms', '');
        });
    });

    // Show / hide password.
    document.querySelectorAll('[data-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle'));
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.classList.toggle('on', show);
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    // Placeholders for features outside this activity (social login, terms, reset).
    var note = document.getElementById('soon');
    document.querySelectorAll('[data-soon]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            if (note) note.hidden = false;
        });
    });
})();
