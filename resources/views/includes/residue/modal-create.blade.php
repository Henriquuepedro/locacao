<div class="modal fade" id="newResidueModal" tabindex="-1" role="dialog" aria-labelledby="newResidueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <form action="{{ route('ajax.residue.new-residue') }}" method="POST" id="formCreateResidueModal">
                <div class="modal-header">
                    <h5 class="modal-title" id="newResidueModalLabel">Cadastro de novo resíduo</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="name">Nome do Resíduo <sup>*</sup></label>
                                    <input type="text" class="form-control" id="name" name="name" autocomplete="nope" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-around">
                    <button type="button" class="btn btn-secondary col-md-3" data-bs-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                    <button type="submit" class="btn btn-success col-md-3"><i class="fa fa-save"></i> Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
