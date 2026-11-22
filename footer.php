<?php
/**
 * Master Footer Layout - Gold & Black Theme
 */
require_once __DIR__ . '/../../config/config.php';
?>
    </main>

    <!-- Footer Information Bar -->
    <footer class="app-footer py-3 px-4 text-center small mt-auto">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div>
                &copy; <?= date('Y') ?> <strong style="color: var(--text-gold-light);"><?= e(APP_NAME) ?></strong> &bull; <?= e(INSTITUTION_NAME) ?>. All rights reserved.
            </div>
            <div style="color: var(--text-muted);">
                <span>Enterprise v2.0</span> &bull; <span style="color: var(--gold);">PHP 8+ & PDO MySQL</span>
            </div>
        </div>
    </footer>
</div><!-- /.app-main -->
</div><!-- /.app-wrapper -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>

<?php if (isset($extraScripts)): ?>
    <?= $extraScripts ?>
<?php endif; ?>
</body>
</html>
