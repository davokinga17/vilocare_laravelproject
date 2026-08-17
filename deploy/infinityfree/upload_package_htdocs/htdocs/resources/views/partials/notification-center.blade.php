@if(($notificationCenter['visible'] ?? false) === true)
    <div class="dropdown notification-menu">
        <button
            class="notification-trigger"
            type="button"
            id="globalNotifications"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            aria-label="Open notifications and quick actions"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
            </svg>
            @if(($notificationCenter['recent_notifications'] ?? collect())->isNotEmpty())
                <span class="notification-count">{{ $notificationCenter['recent_notifications']->count() }}</span>
            @endif
        </button>

        <div class="dropdown-menu dropdown-menu-end notification-panel" aria-labelledby="globalNotifications">
            <div class="notification-panel-head">
                <div>
                    <span>Activity</span>
                    <h2>Notifications</h2>
                </div>
                <small>Quick access</small>
            </div>

            <div class="notification-quick-actions" role="group" aria-label="Notification quick actions">
                <button type="button" class="notification-action" data-notification-view="sms">
                    <span class="notification-action-icon action-sms">SM</span>
                    <span>SMS</span>
                </button>
                <button type="button" class="notification-action" data-notification-view="reminder">
                    <span class="notification-action-icon action-reminder">RM</span>
                    <span>Reminder</span>
                </button>
            </div>

            <div class="notification-reminder" id="notificationReminder" hidden>
                <form method="POST" action="{{ route('dashboard.support.reminder') }}">
                    @csrf
                    <label for="notification_appointment_id">Upcoming appointment</label>
                    <div class="notification-reminder-row">
                        <select id="notification_appointment_id" name="appointment_id" class="form-select form-select-sm">
                            @forelse(($notificationCenter['upcoming_appointments'] ?? collect()) as $appointment)
                                <option value="{{ $appointment->appointment_id }}">
                                    {{ optional($appointment->appointment_date)->format('d M Y') }} - {{ trim(($appointment->patient->first_name ?? '') . ' ' . ($appointment->patient->last_name ?? '')) }}
                                </option>
                            @empty
                                <option value="">No upcoming appointments</option>
                            @endforelse
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm" @disabled(($notificationCenter['upcoming_appointments'] ?? collect())->isEmpty())>Send</button>
                    </div>
                </form>
            </div>

            <div class="notification-feed" id="notificationActivity">
                <div class="notification-feed-head">
                    <strong id="notificationFeedTitle">Recent activity</strong>
                    <button type="button" data-notification-view="all" hidden>Show all</button>
                </div>
                <div class="notification-list">
                    @forelse(($notificationCenter['recent_notifications'] ?? collect()) as $notification)
                        <div class="notification-item" data-notification-channel="{{ strtolower($notification->channel) }}">
                            <span class="support-icon support-icon-{{ strtolower($notification->channel) }}">
                                {{ strtoupper(substr($notification->channel, 0, 2)) }}
                            </span>
                            <div class="notification-item-copy">
                                <strong>{{ ucwords(str_replace('_', ' ', $notification->category)) }}</strong>
                                <span>{{ strtoupper($notification->channel) }} to {{ $notification->recipient ?: 'system' }}</span>
                            </div>
                            <span class="support-status status-{{ strtolower($notification->status) }}">{{ ucfirst($notification->status) }}</span>
                        </div>
                    @empty
                        <div class="notification-empty">No notification activity yet.</div>
                    @endforelse
                    <div class="notification-empty" id="notificationSmsEmpty" hidden>No recent SMS activity.</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var notificationItems = document.querySelectorAll('.notification-item');
            var notificationReminder = document.getElementById('notificationReminder');
            var notificationFeedTitle = document.getElementById('notificationFeedTitle');
            var notificationSmsEmpty = document.getElementById('notificationSmsEmpty');
            var showAllNotifications = document.querySelector('[data-notification-view="all"]');

            document.querySelectorAll('[data-notification-view]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var view = button.getAttribute('data-notification-view');
                    var showReminder = view === 'reminder';
                    var filterSms = view === 'sms';
                    var visibleSmsCount = 0;

                    if (notificationReminder) {
                        notificationReminder.hidden = !showReminder;
                    }

                    notificationItems.forEach(function (item) {
                        var visible = !filterSms || item.getAttribute('data-notification-channel') === 'sms';
                        item.hidden = !visible;
                        visibleSmsCount += filterSms && visible ? 1 : 0;
                    });

                    if (notificationFeedTitle) {
                        notificationFeedTitle.textContent = filterSms ? 'Recent SMS activity' : 'Recent activity';
                    }

                    if (notificationSmsEmpty) {
                        notificationSmsEmpty.hidden = !filterSms || visibleSmsCount > 0;
                    }

                    if (showAllNotifications) {
                        showAllNotifications.hidden = !filterSms;
                    }
                });
            });
        })();
    </script>
@endif
