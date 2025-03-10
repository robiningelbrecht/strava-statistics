<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Integration\AI\EmbeddedDocument;
use App\Domain\Integration\AI\Ollama\Ollama;
use App\Domain\Strava\Activity\ActivityRepository;
use LLPhant\Chat\Message;
use LLPhant\Chat\OllamaChat;
use LLPhant\Embeddings\DataReader\FileDataReader;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingFormatter\EmbeddingFormatter;
use LLPhant\Embeddings\EmbeddingGenerator\Ollama\OllamaEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Memory\MemoryVectorStore;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:ollama:test', description: 'Test Ollama')]
class TestOllamaChatConsoleCommand extends Command
{
    public function __construct(
        private readonly Ollama $ollama,
        private readonly ActivityRepository $activityRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->ollama->isEnabled()) {
            $output->writeln('<error>Ollama is disabled</error>');

            return Command::SUCCESS;
        }

        $config = $this->ollama->getConfig()->toLLPhant();
        $chat = new OllamaChat($config);

        $activities = $this->activityRepository->findAll();
        $documents = [];
        foreach ($activities as $activity) {
            $documents[] = new EmbeddedDocument($activity)->build();
        }

        // Embedding
        // $dataReader = new FileDataReader(__DIR__.'/private-data.txt');
        // $documents = $dataReader->getDocuments();
        var_dump($documents);

        return Command::SUCCESS;

        $splitDocuments = DocumentSplitter::splitDocuments($documents, 500);
        $formattedDocuments = EmbeddingFormatter::formatEmbeddings($splitDocuments);

        $embeddingGenerator = new OllamaEmbeddingGenerator($config);
        $embeddedDocuments = $embeddingGenerator->embedDocuments($formattedDocuments);

        $memoryVectorStore = new MemoryVectorStore();
        $memoryVectorStore->addDocuments($embeddedDocuments);

        // RAG
        $qa = new QuestionAnswering(
            vectorStoreBase: $memoryVectorStore,
            embeddingGenerator: $embeddingGenerator,
            chat: $chat
        );

        $messages = [
            Message::system('You are a workout assistant'),
            Message::user('What can you tell me about the 2024 nobel physics price?'),
        ];
        $answer = $qa->answerQuestionFromChat($messages, 4, [], false);
        // printf("-- Answer:\n%s\n", $answer);
        // $answer = $qa->answerQuestion('Who won The Nobel Prize in Physics 2024?');
        printf("-- Answer:\n%s\n", $answer);
        printf("\n");
        $retrievedDocs = $qa->getRetrievedDocuments();
        printf("We used %d documents to answer the question, as follows:\n\n", count($retrievedDocs));
        foreach ($qa->getRetrievedDocuments() as $doc) {
            printf("-- Document: %s\n", $doc->sourceName);
            printf("-- Hash: %s\n", $doc->hash);
            printf("-- Content of %d characters, extract: %s...\n\n", strlen($doc->content), substr($doc->content, 0, 100));
        }

        return Command::SUCCESS;
    }
}
