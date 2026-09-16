@push('styles')
    <style>
        .privacy-policy-link { padding: 0; border: 0; background: transparent; color: var(--inay-pink); font: inherit; font-weight: 700; text-decoration: underline; cursor: pointer; }
        .privacy-policy-link:focus-visible, .privacy-policy-close:focus-visible { outline: 3px solid var(--inay-pink); outline-offset: 3px; }
        .privacy-policy-dialog { width: min(720px, calc(100% - 32px)); max-height: 85vh; max-height: 85dvh; padding: 0; border: 0; border-radius: 20px; color: var(--inay-text); background: white; box-shadow: 0 24px 80px #111a3240; }
        .privacy-policy-dialog::backdrop { background: rgb(17 26 50 / 60%); }
        .privacy-policy-dialog[open] { display: flex; flex-direction: column; }
        .privacy-policy-header, .privacy-policy-footer { flex-shrink: 0; padding: 20px 24px; background: var(--inay-soft-pink); }
        .privacy-policy-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; border-bottom: 1px solid var(--inay-line); }
        .privacy-policy-header h2 { margin: 0; font-size: 24px; }
        .privacy-policy-header p { margin: 4px 0 0; color: var(--inay-muted); font-size: 14px; }
        .privacy-policy-close { border: 1px solid var(--inay-line); border-radius: 10px; padding: 10px 16px; background: white; color: var(--inay-text); font: inherit; font-weight: 700; cursor: pointer; }
        .privacy-policy-content { min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 8px 24px 24px; line-height: 1.7; font-size: 15px; }
        .privacy-policy-content h3 { margin: 24px 0 8px; font-size: 17px; }
        .privacy-policy-content p { margin: 8px 0; }
        .privacy-policy-content ul { padding-left: 22px; }
        .privacy-policy-content li + li { margin-top: 8px; }
        .privacy-policy-footer { border-top: 1px solid var(--inay-line); text-align: right; }
        html.privacy-policy-open { overflow: hidden; }
    </style>
@endpush

<dialog id="privacyPolicyDialog" class="privacy-policy-dialog" aria-labelledby="privacyPolicyTitle">
    <header class="privacy-policy-header">
        <div>
            <h2 id="privacyPolicyTitle" tabindex="-1">Privacy Policy</h2>
            <p>Project INAY</p>
        </div>
        <button type="button" class="privacy-policy-close" data-close-privacy-policy aria-label="Close Privacy Policy">Close</button>
    </header>

    <div class="privacy-policy-content" tabindex="0" role="region" aria-label="Privacy Policy information">
        <p>Project INAY supports maternal and child health services. This notice explains the information used in the mother and program staff portals and how to ask questions about your data.</p>

        <h3>1. Information collected</h3>
        <ul>
            <li><strong>Account and contact details:</strong> your name, email address, password, contact number, barangay, and profile information you provide.</li>
            <li><strong>Maternal health information:</strong> age, blood type, civil status, pregnancy status, pregnancy and birth history, beneficiary status, checkup records, vital signs, and related care information entered by you or program staff.</li>
            <li><strong>Child health information:</strong> child details, growth measurements, vaccination records, and related health records entered through the child health services.</li>
            <li><strong>Staff information:</strong> staff ID, professional role, and an uploaded healthcare worker ID photo when provided for account verification.</li>
            <li><strong>Service activity:</strong> appointments, consultation messages, uploaded files or photos, notifications, and educational progress recorded in the portal.</li>
            <li><strong>Location:</strong> coordinates and location accuracy when you choose “Use Location” and allow your browser to share your location.</li>
        </ul>

        <h3>2. How information is used</h3>
        <p>Information is used to create and manage accounts, verify program staff, maintain maternal and child health records, coordinate appointments and consultations, support monitoring and follow-up, provide educational resources, and prepare program reports.</p>

        <h3>3. Access to your information</h3>
        <p>Health information entered in the portal is available to program staff through the care and monitoring features. Administrators manage staff accounts and access program administration and reporting features. Messages and files you send are available to the recipients and through the relevant service workflows.</p>
        <p>Only include information relevant to your care or program duties, especially when sending messages or uploading records about another person.</p>

        <h3>4. Location, camera, and microphone permissions</h3>
        <p>Location sharing requires browser permission. Consultation calls may also request camera or microphone permission when you use those features. You can manage these permissions in your browser settings. Features that depend on a permission may be unavailable when it is disabled.</p>

        <h3>5. Cookies and account sessions</h3>
        <p>The portal uses session cookies to support signing in and keeping your account session active. Disabling cookies may prevent account features from working correctly. Sign out after using a shared device and keep your password private.</p>

        <h3>6. Storage and retention</h3>
        <p>Account details and submitted records are stored by the Project INAY system so they can be used for its services. For the retention period that applies to your records, or to request removal of information, contact the program administrator. Removing information may affect access to services and the availability of care records.</p>

        <h3>7. Your choices and requests</h3>
        <p>You can review information available in your portal and update fields supported by your profile. Contact program staff or the administrator to request access to other records, correct inaccurate information, ask about deletion, or discuss withdrawing your agreement. They can explain which requests can be fulfilled and any records that need to be retained.</p>
        <p>For child records, provide information only when you are the parent, guardian, or a staff member authorized to maintain the record.</p>

        <h3>8. Questions and privacy concerns</h3>
        <p>Contact your Project INAY program staff or program administrator for questions about how your information is handled or to report a privacy concern.</p>
        @if(config('contacts.admin_phone'))
            <p>Administrator contact number: {{ config('contacts.admin_phone') }}</p>
        @endif

        <h3>9. Reading and agreeing</h3>
        <p>Read this information before selecting the agreement checkbox on the registration form. Opening or closing this notice does not select that checkbox. If you need clarification, contact the program team before registering.</p>
    </div>

    <footer class="privacy-policy-footer">
        <button type="button" class="privacy-policy-close" data-close-privacy-policy>Back to registration</button>
    </footer>
</dialog>

@push('scripts')
    <script>
        (() => {
            const dialog = document.getElementById('privacyPolicyDialog');
            let opener;

            document.querySelectorAll('[data-open-privacy-policy]').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    opener = button;
                    dialog.showModal();
                    document.documentElement.classList.add('privacy-policy-open');
                    dialog.querySelector('.privacy-policy-content').scrollTop = 0;
                    document.getElementById('privacyPolicyTitle').focus();
                });
            });

            dialog.querySelectorAll('[data-close-privacy-policy]').forEach((button) => {
                button.addEventListener('click', () => dialog.close());
            });

            dialog.addEventListener('click', (event) => {
                const bounds = dialog.getBoundingClientRect();
                if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) {
                    dialog.close();
                }
            });

            dialog.addEventListener('close', () => {
                document.documentElement.classList.remove('privacy-policy-open');
                opener?.focus();
            });
        })();
    </script>
@endpush
