<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>

<div class="main-content content" style="margin-top:75px;">
    <h4 class="fw-bold mb-4"><?= $form_type === 'edit' ? 'Edit File' : 'Upload File' ?></h4>

    <form id="form-file" class="form" enctype="multipart/form-data" style="margin: 10px;">
        <?php if ($form_type === 'edit'): ?>
            <input type="hidden" id="fileid" name="fileid" value="<?= $id ?>">
            <div class="form-group mb-3">
                <label for="filerealname" class="form-label fw-bold">File Name</label>
                <input type="text" class="form-control form-control-sm" id="filerealname" name="filerealname"
                    value="<?= $row['filerealname'] ?>" readonly>
            </div>
        <?php endif; ?>

        <!-- Dropzone untuk upload file baru -->
        <div class="form-group mb-3">
            <label class="form-label fw-bold">Upload New File</label>
            <div id="dropzone-file" class="dropzone"></div>
        </div>

        <div class="modal-footer" style="gap:10px;">
            <button type="button" id="btn-submit" class="btn btn-primary btn-sm d-flex align-items-center">
                <i class="bx bx-check margin-r-2"></i>
                <span class="fw-normal fs-7"><?= ($form_type === 'edit' ? 'Update' : 'Save') ?></span>
            </button>
            <button type="button" class="btn btn-secondary btn-sm d-flex align-items-center"
                onclick="window.location.href='<?= base_url('file') ?>'">
                <i class="bx bx-arrow-back margin-r-2"></i>
                <span class="fw-normal fs-7">Back</span>
            </button>
        </div>
    </form>
</div>

<?= $this->include('template/v_footer') ?>

<link href="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone.css" rel="stylesheet" />
<script src="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone-min.js"></script>

<script>
    Dropzone.autoDiscover = false;

    let myDropzone = new Dropzone("#dropzone-file", {
        url: "<?= base_url('file/upload') ?>", 
        paramName: "file",
        maxFilesize: 50,
        chunking: true,
        forceChunking: true,
        chunkSize: 2000000, 
        retryChunks: true,
        retryChunksLimit: 3,
        acceptedFiles: ".jpg,.jpeg,.png,.pdf,.doc,.docx,.xlsx,.mp4",
        addRemoveLinks: true,
        dictDefaultMessage: "Drag & drop file here",
        maxFiles: 1,
        parallelUploads: 1,
        autoProcessQueue: false
    });

    $('#btn-submit').click(function() {
        myDropzone.processQueue();
    });

    myDropzone.on("success", function(file, response) {
        showNotif(response.success ? 'success' : 'error', response.message);
        if (response.success) {
            window.location.href = "<?= base_url('file') ?>";
        }
    });

    myDropzone.on("error", function(file, errorMessage) {
        showNotif('error', errorMessage);
    });
</script>