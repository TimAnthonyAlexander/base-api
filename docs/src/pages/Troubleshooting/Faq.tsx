import { Box, Typography, Alert, Accordion, AccordionSummary, AccordionDetails } from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import CodeBlock from '../../components/CodeBlock';

export default function FAQ() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                Frequently Asked Questions
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                Common questions about BaseAPI
            </Typography>

            <Typography>
                Find answers to the most commonly asked questions about BaseAPI development, deployment, and best practices.
            </Typography>

            <Accordion sx={{ mt: 3 }}>
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                    <Typography variant="h6">How do I start a new BaseAPI project?</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <CodeBlock language="bash" code={`composer create-project baseapi/baseapi-template my-project
cd my-project
./mason serve`} />
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                    <Typography variant="h6">What PHP version does BaseAPI require?</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography>
                        BaseAPI requires PHP 8.4 or higher. It leverages modern PHP features like property promotion,
                        attributes, and improved type system for better developer experience.
                    </Typography>
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                    <Typography variant="h6">How do I deploy BaseAPI to production?</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <CodeBlock language="bash" code={`# Set production environment
APP_ENV=production

# Clear cache
./mason cache:clear

# Apply migrations
./mason migrate:apply

# Generate API docs
./mason types:generate --openapi --typescript`} />
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                    <Typography variant="h6">Can I use BaseAPI with existing databases?</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography>
                        Yes! BaseAPI can work with existing databases. Create models that match your existing tables,
                        or use custom table names in your models. The migration system can help keep schemas in sync.
                    </Typography>
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                    <Typography variant="h6">How does caching work in BaseAPI?</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography>
                        BaseAPI includes a multi-driver cache system with Array, File, and Redis drivers.
                        Models automatically use tagged caching for smart invalidation. Use cache()-{'>'}put() and cache()-{'>'}get()
                        for manual caching, or Model::cached() for query caching.
                    </Typography>
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                    <Typography variant="h6">Is BaseAPI suitable for large applications?</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography>
                        Absolutely! BaseAPI is designed for performance and scalability. It includes caching,
                        rate limiting, database optimization, and follows enterprise-grade architectural patterns.
                        The DI container and middleware system support complex application requirements.
                    </Typography>
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                    <Typography variant="h6">How do I handle file uploads?</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <CodeBlock language="php" code={`class FileController extends Controller
{
    public UploadedFile $file;
    
    public function post(): JsonResponse
    {
        $this->validate([
            'file' => 'required|file|max:10240|mimes:jpg,png,pdf'
        ]);
        
        $path = $this->file->store('uploads');
        return JsonResponse::ok(['path' => $path]);
    }
}`} />
                </AccordionDetails>
            </Accordion>

            <Accordion>
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                    <Typography variant="h6">Does BaseAPI support real-time features?</Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Typography>
                        BaseAPI focuses on REST API development. For real-time features like WebSockets,
                        you can integrate with external services or use server-sent events with appropriate middleware.
                    </Typography>
                </AccordionDetails>
            </Accordion>

            <Alert severity="info" sx={{ mt: 4 }}>
                <strong>Need More Help?</strong>
                <br />Check the Architecture section for detailed explanations of core concepts.
                <br />Review the Troubleshooting section for specific error solutions.
                <br />Examine the baseapi-template/ directory for working examples.
            </Alert>
        </Box>
    );
}
