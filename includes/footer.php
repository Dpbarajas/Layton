</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    function changeAction(nameForm, newLink, accion = null, redirect = null, require = [], unrequire = []) {
        document.getElementById(nameForm).action = newLink;

        if (accion != null) {
            document.getElementById("accion").value = accion;
        } else {
            document.getElementById("accion").value = document.getElementById("accionOriginal").value;
        }

        if (redirect != null) {
            document.getElementById("origen").value = redirect;
        } else {
            document.getElementById("origen").value = document.getElementById("origenOriginal").value;
        }

        require.forEach(idForm => {
            document.getElementById(idForm).required = true;
        })

        unrequire.forEach(idForm => {
            document.getElementById(idForm).required = false;
        })
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
                    valor = valor.replace('.', ',');
                    input.value = valor + ' €';
                } else {
                    input.value = '';
                }

            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dineroInputs = document.querySelectorAll('.autosubmit');

        dineroInputs.forEach(input => {
            input.addEventListener('click', () => {
                const btn = document.getElementById("btn-submit");
                btn.click();
            });
        });
    });
</script>

<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notesModalLabel">Notas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="notesIframe" src="" style="width:100%; height:80vh; border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

</body>
</html>