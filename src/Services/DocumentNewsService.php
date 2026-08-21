<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use App\Models\Kategorie;
use App\Models\News;
use App\Services\NewsCategoryPermissionService;
use App\Services\NewsPublishNotificationService;
use App\Support\NewsMediaDisk;
use Hwkdo\IntranetAppDokumente\Enums\DocumentNewsTitleImageMode;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class DocumentNewsService
{
    public const CATEGORY_NAME = 'Dokumente';

    public function __construct(
        private readonly NewsCategoryPermissionService $categoryPermissions,
        private readonly NewsPublishNotificationService $publishNotifications,
    ) {}

    public function ensureDokumenteCategory(): Kategorie
    {
        $kategorie = Kategorie::query()->firstOrCreate(
            [
                'name' => self::CATEGORY_NAME,
                'parent_id' => Kategorie::NEWS_ROOT_ID,
            ],
            [
                'subscription_required' => false,
            ],
        );

        return $this->categoryPermissions->attachToNewsRoot($kategorie);
    }

    public function publishForCreated(
        Document $document,
        int $publisherId,
        bool $showInSlider,
        DocumentNewsTitleImageMode $titleImageMode = DocumentNewsTitleImageMode::Auto,
        UploadedFile|string|null $customTitleImage = null,
    ): News {
        return $this->publish(
            document: $document,
            publisherId: $publisherId,
            title: 'Neues Dokument: '.$document->title,
            showInSlider: $showInSlider,
            isUpdate: false,
            titleImageMode: $titleImageMode,
            customTitleImage: $customTitleImage,
        );
    }

    public function publishForUpdated(
        Document $document,
        int $publisherId,
        bool $showInSlider,
        DocumentNewsTitleImageMode $titleImageMode = DocumentNewsTitleImageMode::Auto,
        UploadedFile|string|null $customTitleImage = null,
    ): News {
        return $this->publish(
            document: $document,
            publisherId: $publisherId,
            title: 'Aktualisiertes Dokument: '.$document->title,
            showInSlider: $showInSlider,
            isUpdate: true,
            titleImageMode: $titleImageMode,
            customTitleImage: $customTitleImage,
        );
    }

    protected function publish(
        Document $document,
        int $publisherId,
        string $title,
        bool $showInSlider,
        bool $isUpdate,
        DocumentNewsTitleImageMode $titleImageMode,
        UploadedFile|string|null $customTitleImage,
    ): News {
        $document->loadMissing(['uploader', 'responsible', 'category', 'currentVersion.media']);

        $kategorie = $this->ensureDokumenteCategory();
        $url = route('apps.dokumente.index', ['document' => $document->id]);
        $body = $this->buildBodyText($document, $isUpdate);
        $short = Str::limit($body, 200);
        $content = '<p>'.e($body).'</p><p><a href="'.e($url).'">Dokument öffnen</a></p>';

        $news = News::query()->create([
            'title' => $title,
            'short' => $short,
            'content' => $content,
            'slug' => $this->uniqueSlug($title),
            'publisher_id' => $publisherId,
            'is_published' => true,
            'published_at' => now(),
            'kategorie_id' => $kategorie->id,
            'is_slider' => $showInSlider,
        ]);

        $this->attachTitleImage($news, $document, $titleImageMode, $customTitleImage);
        $this->publishNotifications->notifyIfPublished($news);

        return $news->fresh();
    }

    protected function buildBodyText(Document $document, bool $isUpdate): string
    {
        $uploader = $document->uploader?->name ?? 'Unbekannt';
        $responsible = $document->responsible?->name ?? 'Unbekannt';
        $type = $document->category?->name ?? 'Unbekannt';
        $description = trim((string) ($document->description ?? ''));
        if ($description === '') {
            $description = 'Keine Beschreibung.';
        }

        if ($isUpdate) {
            return 'Es wurde eine neue Version des Dokuments von '.$uploader
                .' angelegt. Das Dokument ist vom Typ '.$type
                .'. Verantwortlicher für das Dokument ist '.$responsible
                .'. Beschreibung des Dokuments: '.$description;
        }

        return 'Es wurde ein neues Dokument von '.$uploader
            .' angelegt. Das Dokument ist vom Typ '.$type
            .'. Verantwortlicher für das Dokument ist '.$responsible
            .'. Beschreibung des Dokuments: '.$description;
    }

    protected function attachTitleImage(
        News $news,
        Document $document,
        DocumentNewsTitleImageMode $mode,
        UploadedFile|string|null $customTitleImage,
    ): void {
        if ($mode === DocumentNewsTitleImageMode::Default) {
            return;
        }

        if ($mode === DocumentNewsTitleImageMode::Custom) {
            if ($customTitleImage instanceof UploadedFile) {
                $news->setTitleImage($customTitleImage);

                return;
            }

            if (is_string($customTitleImage) && is_file($customTitleImage)) {
                $news->addMedia($customTitleImage)
                    ->usingName('title')
                    ->preservingOriginal()
                    ->withResponsiveImages()
                    ->toMediaCollection('title', NewsMediaDisk::name());
            }

            return;
        }

        $this->attachComposedDocumentCover($news, $document);
    }

    protected function attachComposedDocumentCover(News $news, Document $document): void
    {
        $documentMedia = $document->currentVersion?->getFirstMedia('document');
        if ($documentMedia === null) {
            return;
        }

        $thumbPath = null;
        if ($documentMedia->hasGeneratedConversion('thumb')) {
            $candidate = $documentMedia->getPath('thumb');
            if (is_string($candidate) && is_file($candidate)) {
                $thumbPath = $candidate;
            }
        }

        if ($thumbPath === null && str_starts_with((string) $documentMedia->mime_type, 'image/')) {
            $candidate = $documentMedia->getPath();
            if (is_string($candidate) && is_file($candidate)) {
                $thumbPath = $candidate;
            }
        }

        if ($thumbPath === null) {
            return;
        }

        $composedPath = null;

        try {
            $composer = app(DocumentNewsCoverComposer::class);
            $composer->ensureDefaultSeeded();
            $composedPath = $composer->compose($thumbPath);

            $news->addMedia($composedPath)
                ->usingName('title')
                ->usingFileName('dokument-news-cover.jpg')
                ->toMediaCollection('title', NewsMediaDisk::name());
        } catch (\Throwable $e) {
            report($e);

            $news->addMedia($thumbPath)
                ->usingName('title')
                ->preservingOriginal()
                ->withResponsiveImages()
                ->toMediaCollection('title', NewsMediaDisk::name());
        } finally {
            if (is_string($composedPath) && is_file($composedPath)) {
                @unlink($composedPath);
            }
        }
    }

    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 80, ''));
        if ($base === '') {
            $base = 'dokument';
        }

        $slug = $base;
        $i = 1;
        while (News::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
