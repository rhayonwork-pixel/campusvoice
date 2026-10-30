<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/icons.php';

$me = current_user();

// Live numbers for the stats strip (real data, not hard-coded).
$stats = q('SELECT COUNT(*) total, SUM(status IN ("Resolved","Closed")) done FROM concerns')->fetch();
$total = (int)$stats['total'];
$done  = (int)$stats['done'];
$rate  = $total ? (int)round($done / $total * 100) : 0;
$avg   = q('SELECT ROUND(AVG(rating),1) FROM feedback')->fetchColumn();
$contactEmail = setting('contact_email', 'cmuofficial@cityofmalabonuniversity.edu.ph');
$contactAddr  = setting('contact_address', 'Pampano cor. Maya-Maya Sts., Longos, City of Malabon');

html_head('Your campus, your voice', ['landing.css'], 'landing');
icon_sprite();
?>
<header class="nav" id="nav">
  <a class="brand" href="#top" aria-label="CampusVoice home">
    <?= logo('', 46) ?>
    <span class="brand-text"><b>CampusVoice</b><small>City of Malabon University</small></span>
  </a>
  <nav class="nav-links" aria-label="Primary">
    <a href="#how">How it works</a>
    <a href="#features">Features</a>
    <a href="#roles">Who it's for</a>
    <a href="#follow">Follow us</a>
  </nav>
  <?php if ($me): ?>
    <a class="btn btn-gold btn-sm" href="<?= e(url(home_for($me['role']))) ?>">Open my dashboard</a>
  <?php else: ?>
    <a class="btn btn-gold btn-sm" href="<?= e(url('login.php')) ?>">Login</a>
  <?php endif; ?>
</header>

<main id="top">
  <!-- HERO: a continuous photo slideshow. No buttons, arrows or slide titles. -->
  <section class="hero" id="hero" aria-label="CampusVoice">
    <div class="hero-photos" aria-hidden="true">
      <div class="hp on a" style="background-image:url('<?= e(url('assets/img/cmu-administration-building.png')) ?>')"></div>
      <div class="hp b" style="background-image:url('<?= e(url('assets/img/cmu-campus-courtyard.png')) ?>')"></div>
      <div class="hp c" style="background-image:url('<?= e(url('assets/img/cmu-administration-building.png')) ?>')"></div>
    </div>
    <div class="hero-shade" aria-hidden="true"></div>
    <div class="hero-copy">
      <span class="eyebrow"><span class="wave"><i></i><i></i><i></i><i></i><i></i></span> City of Malabon University</span>
      <h1>Your campus.<br><em>Your voice.</em></h1>
      <p>Report a concern, follow it to the end, and see it resolved — all in one place.</p>
    </div>
    <div class="hero-cue" aria-hidden="true"><span></span></div>
  </section>

  <!-- LIVE STATS -->
  <section class="stats" aria-label="CampusVoice in numbers">
    <div class="stat reveal-up"><b data-count="<?= $total ?>">0</b><span>Concerns reported</span></div>
    <div class="stat reveal-up" style="--d:.08s"><b data-count="<?= $done ?>">0</b><span>Resolved &amp; closed</span></div>
    <div class="stat reveal-up" style="--d:.16s"><b data-count="<?= $rate ?>" data-suffix="%">0</b><span>Resolution rate</span></div>
    <div class="stat reveal-up" style="--d:.24s"><b><?= $avg !== null && $avg !== false ? e($avg) . '<small>/5</small>' : '—' ?></b><span>Student satisfaction</span></div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="section" id="how">
    <div class="wrap">
      <div class="section-head reveal-up"><span class="kicker">How it works</span><h2>From “someone should fix that”<br>to “it's fixed”.</h2></div>
      <ol class="steps">
        <li class="step reveal-up"><span class="num">01</span><h3>Report</h3><p>Describe the concern, pick the location and how severe it is, add a photo.</p></li>
        <li class="step reveal-up" style="--d:.1s"><span class="num">02</span><h3>Assign</h3><p>The admin reviews it and assigns the right staff member.</p></li>
        <li class="step reveal-up" style="--d:.2s"><span class="num">03</span><h3>Act</h3><p>Staff work on it and post updates and proof while you follow along.</p></li>
        <li class="step reveal-up" style="--d:.3s"><span class="num">04</span><h3>Rate</h3><p>Once it is resolved, tell us how we did. Your rating helps us improve.</p></li>
      </ol>
    </div>
  </section>

  <!-- FEATURES -->
  <section class="section alt" id="features">
    <div class="wrap">
      <div class="section-head reveal-up"><span class="kicker">Built for CMU</span><h2>Everything a concern needs,<br>nothing it doesn't.</h2></div>
      <ul class="feat-list">
        <li class="reveal-up"><?= icon('alert') ?><div><h3>Severity levels</h3><p>Minor, Major or Urgent — each with its own deadline, so serious problems never wait.</p></div></li>
        <li class="reveal-up" style="--d:.06s"><?= icon('eyeOff') ?><div><h3>Anonymous option</h3><p>Hide your name from staff and admins and still follow your own concern.</p></div></li>
        <li class="reveal-up" style="--d:.12s"><?= icon('image') ?><div><h3>Photo evidence</h3><p>Attach photos and open them full-size without leaving the page.</p></div></li>
        <li class="reveal-up" style="--d:.18s"><?= icon('activity') ?><div><h3>Live status timeline</h3><p>Every update is stamped, so you always know who did what, and when.</p></div></li>
        <li class="reveal-up" style="--d:.24s"><?= icon('star') ?><div><h3>Rate the fix</h3><p>Give a 1–5 star rating when it is done. Feedback goes straight to the admin.</p></div></li>
        <li class="reveal-up" style="--d:.3s"><?= icon('calendar') ?><div><h3>Deadlines on a calendar</h3><p>Deadlines, assignments and campus events in one view for the administrator.</p></div></li>
      </ul>
    </div>
  </section>

  <!-- ROLES -->
  <section class="section" id="roles">
    <div class="wrap">
      <div class="section-head reveal-up"><span class="kicker">Who it's for</span><h2>One system, three views.</h2></div>
      <div class="role-tabs reveal-up" role="tablist">
        <button class="on" data-role="student" role="tab">Students</button>
        <button data-role="staff" role="tab">Staff</button>
        <button data-role="admin" role="tab">Admin</button>
      </div>
      <div class="role-panels">
        <div class="role-panel on" data-panel="student"><ul><li>Report concerns and attach photos</li><li>Choose Minor, Major or Urgent</li><li>Send anonymously when you need to</li><li>Follow the timeline and rate the result</li></ul></div>
        <div class="role-panel" data-panel="staff"><ul><li>See today's assigned work with “New” highlights</li><li>Update status and post proof of the fix</li><li>Set your availability so work is shared fairly</li><li>Open photos instantly in a viewer</li></ul></div>
        <div class="role-panel" data-panel="admin"><ul><li>Triage urgent concerns first</li><li>Assign and reassign with a live staff-workload view</li><li>Audit trail of every important action</li><li>Reports, calendar and system settings</li></ul></div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta-band">
    <div class="wrap reveal-up">
      <h2>Ready to be heard?</h2>
      <p>Sign in with your university account, or create a student account in a minute.</p>
      <div class="cta">
        <a class="btn btn-gold" href="<?= e(url($me ? home_for($me['role']) : 'login.php')) ?>"><?= $me ? 'Open dashboard' : 'Login' ?></a>
        <?php if (!$me): ?><a class="btn btn-outline" href="<?= e(url('signup.php')) ?>">Create account</a><?php endif; ?>
      </div>
    </div>
  </section>

  <!-- FOLLOW -->
  <section class="follow" id="follow">
    <div class="wrap follow-grid">
      <div class="follow-copy reveal-up">
        <span class="kicker">Stay in the loop</span>
        <h2>Follow CampusVoice</h2>
        <p>Announcements, campus updates and resolved-concern highlights — on the platforms you already use.</p>
        <p class="contact"><?= e($contactAddr) ?><br><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></p>
      </div>
      <div class="socials reveal-up" style="--d:.1s">
        <!-- Placeholder links: replace each href="#" with the official page URL. -->
        <a class="social" href="#" aria-label="CampusVoice on Facebook" data-net="Facebook"><svg viewBox="0 0 24 24"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v7h4v-7h3l1-4h-4V8z"/></svg><span>Facebook</span><?= icon('chevR') ?></a>
        <a class="social" href="#" aria-label="CampusVoice on Instagram" data-net="Instagram"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1" fill="currentColor"/></svg><span>Instagram</span><?= icon('chevR') ?></a>
        <a class="social" href="#" aria-label="CampusVoice on X" data-net="X"><svg viewBox="0 0 24 24"><path d="M4 4l16 16M20 4L4 20"/></svg><span>X</span><?= icon('chevR') ?></a>
        <a class="social" href="#" aria-label="CampusVoice on YouTube" data-net="YouTube"><svg viewBox="0 0 24 24"><rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10 9.5v5l4.5-2.5z" fill="currentColor"/></svg><span>YouTube</span><?= icon('chevR') ?></a>
        <a class="social" href="#" aria-label="CampusVoice on TikTok" data-net="TikTok"><svg viewBox="0 0 24 24"><path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5M14 3c.3 2.4 2 4 4.5 4.2"/></svg><span>TikTok</span><?= icon('chevR') ?></a>
      </div>
    </div>
    <div class="legal">© <?= date('Y') ?> CampusVoice · City of Malabon University</div>
  </section>
</main>

<?php html_foot(['landing.js']); ?>
