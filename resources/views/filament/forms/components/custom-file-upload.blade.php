<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        isUploading: false,
        progress: 0,
        previews: [],
        handleFiles(event) {
            const files = Array.from(event.target.files);
            if (!files.length) return;
    
            this.isUploading = true;
            this.progress = 0;
    
            const newPreviews = files.map(file => URL.createObjectURL(file));
    
            $wire.uploadMultiple('{{ $getStatePath() }}', files,
                (uploadedFiles) => {
                    this.isUploading = false;
                    this.progress = 0;
                    this.previews = [...this.previews, ...newPreviews];
                },
                (error) => {
                    this.isUploading = false;
                    this.progress = 0;
                    newPreviews.forEach(url => URL.revokeObjectURL(url));
                    console.error('Upload error:', error);
                },
                (event) => {
                    this.progress = event.detail.progress;
                }
            );
        },
        removeFile(index) {
            if (this.previews[index]) {
                URL.revokeObjectURL(this.previews[index]);
            }
            this.state.splice(index, 1);
            this.previews.splice(index, 1);
        }
    }" class="custom-file-upload-container">
        <div class="flex items-center justify-center w-full">
            <label
                class="flex flex-col items-center justify-center w-full min-h-[110px] border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 hover:border-[#ff3b5c]/50 transition-all group relative overflow-hidden"
                :class="isUploading ? 'opacity-50 pointer-events-none' : ''">
                <div class="flex flex-col items-center justify-center pt-4 pb-5 px-4 text-center">
                    <div
                        class="w-10 h-10 mb-2 rounded-full bg-white shadow-sm flex items-center justify-center group-hover:scale-110 transition-transform">
                        <x-heroicon-o-camera class="w-5 h-5 text-gray-400 group-hover:text-[#ff3b5c]" />
                    </div>
                    <p class="mb-1 text-sm text-gray-600 font-medium">
                        <span class="text-[#ff3b5c]">Click to upload screenshots</span>
                    </p>
                    <p class="text-xs text-gray-400">PNG, JPG, WEBP (Multiple allowed)</p>
                </div>
                <input type="file" class="hidden" multiple accept="image/*" x-on:change="handleFiles" />

                <!-- Uploading Overlay -->
                <template x-if="isUploading">
                    <div class="absolute inset-0 bg-white/80 flex flex-col items-center justify-center p-4">
                        <div class="w-full max-w-[150px] bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-[#ff3b5c] h-full transition-all duration-300"
                                :style="'width: ' + progress + '%'">
                            </div>
                        </div>
                        <p class="mt-2 text-[10px] font-bold text-[#ff3b5c]" x-text="'Uploading ' + progress + '%'">
                    </div>
                </template>
            </label>
        </div>

        <!-- Preview Grid -->
        <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 mt-3" x-show="state && state.length > 0">
            <!-- Persistent Previews (from state) -->
            <template x-for="(file, index) in state" :key="index">
                <div
                    class="relative group aspect-square rounded-lg overflow-hidden border border-gray-100 bg-gray-50 shadow-sm">
                    <img :src="file.startsWith('livewire-file:') ? previews[index] : '/storage/' + file"
                        class="w-full h-full object-cover">
                    <div
                        class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <button type="button" x-on:click="removeFile(index)"
                            class="p-1 rounded-full bg-white/20 hover:bg-red-500 text-white transition-colors">
                            <x-heroicon-o-x-mark class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>
