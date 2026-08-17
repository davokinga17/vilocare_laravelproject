<div class="modal fade" id="smsComposeModal" tabindex="-1" aria-labelledby="smsComposeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="smsComposeForm">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="smsComposeModalLabel">Send SMS</h5>
                        <p class="text-muted mb-0 small" id="smsComposeModalHint">Verify the phone number and customize the message before sending.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="smsComposePhone" class="form-label">Phone number</label>
                        <input
                            type="text"
                            class="form-control"
                            id="smsComposePhone"
                            name="phone"
                            maxlength="30"
                            placeholder="+2119XXXXXXXX"
                            required
                        >
                    </div>
                    <div class="mb-0">
                        <label for="smsComposeMessage" class="form-label">Message</label>
                        <textarea
                            class="form-control"
                            id="smsComposeMessage"
                            name="message"
                            rows="6"
                            maxlength="1000"
                            required
                        ></textarea>
                        <div class="form-text">Keep messages clear and patient-safe. Maximum 1000 characters.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send SMS</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var modalElement = document.getElementById('smsComposeModal');

        if (!modalElement || typeof bootstrap === 'undefined') {
            return;
        }

        var modal = new bootstrap.Modal(modalElement);
        var form = document.getElementById('smsComposeForm');
        var title = document.getElementById('smsComposeModalLabel');
        var hint = document.getElementById('smsComposeModalHint');
        var phoneInput = document.getElementById('smsComposePhone');
        var messageInput = document.getElementById('smsComposeMessage');

        document.querySelectorAll('[data-sms-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                form.setAttribute('action', button.getAttribute('data-sms-action') || '');
                title.textContent = button.getAttribute('data-sms-title') || 'Send SMS';
                hint.textContent = button.getAttribute('data-sms-hint') || 'Verify the phone number and customize the message before sending.';
                phoneInput.value = button.getAttribute('data-sms-phone') || '';
                messageInput.value = button.getAttribute('data-sms-message') || '';
                modal.show();
            });
        });
    })();
</script>
