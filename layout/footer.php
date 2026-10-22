<?php
// Layout footer partial
?>
        </div><!-- /.app-content-body -->

        <!-- APP FOOTER -->
        <footer class="app-footer">
            <div>
                <strong>Karyawan-WA</strong> &copy; <?= date('Y') ?> &mdash; Sistem Manajemen Karyawan &amp; WhatsApp Gateway.
            </div>
            <div>
                Waktu Server: <span style="font-family:monospace;"><?= date('H:i:s') ?> WIB</span>
            </div>
        </footer>
    </main><!-- /.app-main -->

</div><!-- /.app-layout -->

<script src="<?= e(app_url('assets/app.js')) ?>"></script>
</body>
</html>

