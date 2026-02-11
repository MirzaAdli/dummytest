<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>
<style>
    .main-content {
        margin-top: 100px;
    }
</style>
<div class="main-content content">
    <h5 class="fw-bold mb-3"><?= ($form_type == 'edit') ? 'Edit File' : 'Add File' ?></h5>
    <form id="form-file" class="form" enctype="multipart/form-data" action="<?= base_url('file/upload') ?>" method="post">

        <!-- Area Dropzone -->
        <div class="form-group mb-3">
            <label for="dropzone" class="form-label fw-bold">Upload File</label>
            <div class="dropzone" id="myDropzone"></div>
        </div>

        <!-- Input Description -->
        <div class="form-group mb-3">
            <label for="description" class="form-label fw-bold">Description</label>
            <textarea class="form-control form-control-sm" id="description" name="description" rows="3"></textarea>
        </div>

        <!-- Tombol Simpan -->
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
<script src="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone-min.js"></script>
<link href="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone.css" rel="stylesheet" type="text/css" />
<script>
    Dropzone.autoDiscover = false; // penting supaya tidak auto-init
    var myDropzone = new Dropzone("#myDropzone", {
        url: "<?= base_url('file/upload') ?>",
        paramName: "file",
        maxFilesize: 5, // MB
        acceptedFiles: ".jpg,.png,.pdf,.docx",
        addRemoveLinks: true,
        init: function() {
            this.on("success", function(file, response) {
                console.log("Upload sukses:", response);
            });
            this.on("error", function(file, errorMessage) {
                console.error("Upload gagal:", errorMessage);
            });
        }
    });
</script>