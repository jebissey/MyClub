<?php
require_once 'NavManager.php';
$navManager = new NavManager();
$navItems = $navManager->getNavItems();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Navigation Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Navigation List -->
        <div class="row">
            <div class="col-md-6">
                <h3>Navigation Items</h3>
                <ul id="navList" class="list-group">
                    <?php foreach ($navItems as $item): ?>
                    <li class="list-group-item" data-id="<?php echo $item['Id']; ?>">
                        <?php echo htmlspecialchars($item['Name']); ?>
                        <button class="btn btn-sm btn-primary float-end edit-btn">Edit</button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <button id="addNew" class="btn btn-success mt-3">Add New Item</button>
            </div>
        </div>
        
        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Navigation Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editForm">
                            <input type="hidden" id="itemId">
                            <div class="mb-3">
                                <label>Name:</label>
                                <input type="text" class="form-control" id="itemName">
                            </div>
                            <div class="mb-3">
                                <label>File:</label>
                                <input type="text" class="form-control" id="itemFile">
                            </div>
                            <div class="mb-3">
                                <label>Content:</label>
                                <div id="editor"></div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveChanges">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize drag and drop
        $('#navList').sortable({
            update: function(event, ui) {
                const positions = {};
                $('#navList li').each(function(index) {
                    positions[index] = $(this).data('id');
                });
                
                $.post('update_positions.php', { positions: positions });
            }
        });
        
        // Initialize WYSIWYG editor
        $('#editor').summernote({
            height: 300,
            callbacks: {
                onImageUpload: function(files) {
                    const formData = new FormData();
                    formData.append('file', files[0]);
                    
                    $.ajax({
                        url: 'upload_image.php',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(url) {
                            $('#editor').summernote('insertImage', url);
                        }
                    });
                },
                onMediaDelete: function(target) {
                    const src = target[0].src;
                    $.post('delete_image.php', { path: src });
                }
            }
        });
        
        // Handle edit button clicks
        $('.edit-btn').click(function() {
            const id = $(this).closest('li').data('id');
            $.get('get_item.php', { id: id }, function(data) {
                $('#itemId').val(data.Id);
                $('#itemName').val(data.Name);
                $('#itemFile').val(data.File);
                $('#editor').summernote('code', data.Content);
                $('#editModal').modal('show');
            });
        });
        
        // Handle save changes
        $('#saveChanges').click(function() {
            const data = {
                id: $('#itemId').val(),
                name: $('#itemName').val(),
                file: $('#itemFile').val(),
                content: $('#editor').summernote('code')
            };
            
            $.post('save_item.php', data, function() {
                $('#editModal').modal('hide');
                location.reload();
            });
        });
        
        // Handle add new item
        $('#addNew').click(function() {
            $('#itemId').val('');
            $('#itemName').val('');
            $('#itemFile').val('');
            $('#editor').summernote('code', '');
            $('#editModal').modal('show');
        });
    });
    </script>
</body>
</html>