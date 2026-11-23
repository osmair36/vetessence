<div>
    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-md-7">
                <div class="form-group">
                    <label>Produto Vinculado (opcional)</label>
                    <select wire:model="product_id" class="form-control">
                        <option value="">— Nenhum (aplicar por nome) —</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label>Tipo de Protocolo</label>
                    <select wire:model="protocol_type" class="form-control">
                        <option value="vaccine">Vacina</option>
                        <option value="antiparasitic">Antiparasitário / Antipulgas</option>
                        <option value="dewormer">Vermífugo</option>
                        <option value="medication">Medicamento Periódico</option>
                        <option value="other">Outro Produto</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Espécie *</label>
                    <select wire:model="species" class="form-control @error('species') is-invalid @enderror" required>
                        <option value="">Selecione...</option>
                        <option value="all">Todas as espécies</option>
                        @php $speciesList = config('species'); @endphp
                        @foreach($speciesList as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('species') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nome do Protocolo / Produto *</label>
                    <input type="text" wire:model="vaccine_name" class="form-control @error('vaccine_name') is-invalid @enderror" required>
                    @error('vaccine_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Idade Início (semanas)</label>
                    <input type="number" wire:model="age_start_weeks" class="form-control" min="0">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Idade Fim (semanas)</label>
                    <input type="number" wire:model="age_end_weeks" class="form-control" min="0">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Dose Número</label>
                    <input type="number" wire:model="dose_number" class="form-control" min="1">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Intervalo Próx.</label>
                    <input type="number" wire:model="interval_value" class="form-control @error('interval_value') is-invalid @enderror" min="1" placeholder="Ex: 21, 30">
                    @error('interval_value') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Unidade</label>
                    <select wire:model="interval_unit" class="form-control @error('interval_unit') is-invalid @enderror">
                        <option value="">—</option>
                        <option value="days">Dias</option>
                        <option value="weeks">Semanas</option>
                        <option value="months">Meses</option>
                    </select>
                    @error('interval_unit') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Reforço (meses)</label>
                    <input type="number" wire:model="booster_interval_months" class="form-control" min="1">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" wire:model="is_initial" class="custom-control-input" id="vpIsInitial">
                        <label class="custom-control-label" for="vpIsInitial">Série Inicial</label>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" wire:model="is_core" class="custom-control-input" id="vpIsCore">
                        <label class="custom-control-label" for="vpIsCore">Essencial</label>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" wire:model="is_active" class="custom-control-input" id="vpIsActive">
                        <label class="custom-control-label" for="vpIsActive">Ativo</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Observações</label>
            <textarea wire:model="notes" class="wysiwyg form-control @error('notes') is-invalid @enderror" rows="2"></textarea>
            @error('notes') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        <div class="text-right">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Salvar</button>
        </div>
    </form>
</div>
