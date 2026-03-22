</div>

    <script>
        function cambiarAccion(nameForm, newLink, accion = null, redirigir = null, claseARequerir = null, claseANoRequerir = null) {
            const form = document.getElementById(nameForm);

            form.action = newLink;

            if (accion != null) {
                document.getElementById("accion").value = accion;
            }
            if (redirigir != null) {
                document.getElementById("origen").value = redirigir;
            }

            document.querySelectorAll(`.${claseANoRequerir}`).forEach((label) => {
                document.getElementById(label.htmlFor).required = false;
                console.log(label.htmlFor);
            })

            console.log("-------------------");

            document.querySelectorAll(`.${claseARequerir}`).forEach((label) => {
                document.getElementById(label.htmlFor).required = true;
                console.log(label.htmlFor);
            });

        }
    </script>

<script>
    // Save input name and caret position before submitting
    document.querySelectorAll('form input').forEach(input => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                const data = {
                    name: this.name,
                    caret: this.selectionStart
                };
                sessionStorage.setItem('lastFocusedInput', JSON.stringify(data));
            }
        });
    });

    // Restore focus and caret after page reload
    window.addEventListener('DOMContentLoaded', () => {
        const saved = sessionStorage.getItem('lastFocusedInput');
        if (saved && saved.type !== "number") {
            try {
                const { name, caret } = JSON.parse(saved);
                const input = document.querySelector(`[name="${name}"]`);
                if (input) {
                    input.focus();
                    input.setSelectionRange(caret, caret); // restore caret position
                }
            } catch (e) {
                console.error('Could not parse stored caret info:', e);
            }
            sessionStorage.removeItem('lastFocusedInput');
        }
    });
</script>

<script>
    jQuery(document).ready(function($) {
        $(".clickable").click(function() {
            window.location = $(this).data("url");
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Cambiar el tipo de los inputs monetarios a tipo texto o numero para poder visualizarlos con €
        const dineroInputs = document.querySelectorAll('.dinero');

        dineroInputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.value = parseFloat(input.value.replace(/[^\d.,-]/g, '').replace(',', '.')) || 0

                input.type = 'number';
            });

            input.addEventListener('blur', () => {
                input.type = 'text';
                let valor = parseFloat(input.value);

                if (!isNaN(valor)) {
                    valor = valor.toFixed(2)
                    input.value = valor + ' €';
                } else {
                    input.value = '';
                }

            });

            input.type = 'text';
            let valor = parseFloat(input.value);

            if (!isNaN(valor)) {
                valor = valor.toFixed(2)
                input.value = valor + ' €';
            } else {
                input.value = '';
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const submitInputs = document.querySelectorAll('.autosubmit');

        submitInputs.forEach(input => {
            input.addEventListener('click', () => {
                const btn = document.getElementById("btn-submit");
                btn.click();
            });
        });
    });
</script>

</body>
</html>