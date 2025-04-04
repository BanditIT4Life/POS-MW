<h2>Import Item Kits from CSV</h2>

<form method="post" enctype="multipart/form-data" action="<?= site_url('item_kits/process_import') ?>">
    <input type="file" name="file" accept=".csv" required>
    
    <!-- ✅ Add CSRF token -->
    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
           value="<?= $this->security->get_csrf_hash(); ?>" />

    <br><br>
    <button type="submit" class="btn btn-primary">Upload</button>
</form>