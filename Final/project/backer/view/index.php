<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backer Dashboard - CrowdFund</title>
    <link rel="stylesheet" href="../../shared/fontawesome/css/all.min.css">
    <style>
        :root { --bg:#f6f7f8; --card:#fff; --text:#111; --muted:#6b7280; --border:#e5e7eb; --radius:12px; --accent:#111; }
        *{box-sizing:border-box}
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; margin:0; background: var(--bg); color: var(--text); }
        .container { max-width: 1100px; margin: 0 auto; padding: 24px; }
        .header { display:flex; align-items:center; justify-content:space-between; background: var(--card); padding:16px 20px; border:1px solid var(--border); border-radius: var(--radius); margin-bottom:16px; }
        .title { font-size: 20px; font-weight: 600; }
        .link { color:#ef4444; text-decoration:none; }
        .grid { display:grid; grid-template-columns: repeat(12, 1fr); gap:16px; }
        .card { background: var(--card); border:1px solid var(--border); border-radius: var(--radius); padding:16px; }
        .col-8 { grid-column: span 8; }
        .col-4 { grid-column: span 4; }
        .section-title { font-size:16px; font-weight:600; margin:0 0 12px; }
        .donation { display:flex; align-items:center; justify-content:space-between; padding:12px; border:1px solid var(--border); border-radius:10px; margin-bottom:8px; }
        .donation .left { display:flex; gap:12px; align-items:center; }
        .avatar { width:36px; height:36px; border-radius:50%; background:#f3f4f6; display:flex; align-items:center; justify-content:center; color:#6b7280; }
        .muted { color: var(--muted); font-size:12px; }
        .amount { font-weight:600; }
        .list-empty { color: var(--muted); padding:8px 0; }
        a.btn { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid var(--border); border-radius:8px; text-decoration:none; color:var(--text); }
        .fund { padding:12px; border:1px solid var(--border); border-radius:10px; margin-bottom:8px; display:flex; align-items:center; justify-content:space-between; }
        .fund .name { font-weight:600; }
        .fund .actions a { margin-left:8px; }
    </style>
<?php
require_once '../../includes/session.php';
require_once '../../includes/functions.php';
requireLogin();
requireRole('backer');
$user = getCurrentUser();
$fm = new FundManager();
$donations = $fm->getUserDonations($user['id'], 25);
$liked = $fm->getUserLikedFunds($user['id'], 25);
?>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title"><i class="fas fa-hand-holding-heart"></i> Backer Dashboard</div>
            <a class="link" href="../../includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="grid">
            <div class="card col-8">
                <h3 class="section-title">Recent Donations</h3>
                <?php if (empty($donations)): ?>
                    <div class="list-empty">You haven't donated yet. Explore campaigns to get started.</div>
                <?php else: ?>
                    <?php foreach ($donations as $d): ?>
                        <div class="donation">
                            <div class="left">
                                <div class="avatar"><i class="fas fa-donate"></i></div>
                                <div>
                                    <div><strong><?php echo formatCurrency($d['amount']); ?></strong> to <a class="btn" href="../../campaign/view.php?id=<?php echo $d['fund_id']; ?>"><?php echo htmlspecialchars($d['fund_title']); ?></a></div>
                                    <div class="muted"><?php echo timeAgo($d['created_at']); ?> • <?php echo $d['payment_status']; ?></div>
                                </div>
                            </div>
                            <div class="amount"><?php echo $d['anonymous'] ? 'Anonymous' : ''; ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card col-4">
                <h3 class="section-title">Liked Campaigns</h3>
                <?php if (empty($liked)): ?>
                    <div class="list-empty">No liked campaigns yet.</div>
                <?php else: ?>
                    <?php foreach ($liked as $f): ?>
                        <div class="fund">
                            <div>
                                <div class="name"><?php echo htmlspecialchars($f['title']); ?></div>
                                <div class="muted">by <?php echo htmlspecialchars($f['fundraiser_name']); ?></div>
                            </div>
                            <div class="actions">
                                <a class="btn" href="../../campaign/view.php?id=<?php echo $f['id']; ?>"><i class="fas fa-eye"></i> View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
