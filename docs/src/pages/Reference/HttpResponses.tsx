
import { Box, Typography, Alert, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';

export default function HTTPResponses() {
    return (
        <Box>
            <Typography variant="h1" gutterBottom>
                HTTP Response Helpers
            </Typography>
            <Typography variant="h5" color="text.secondary" paragraph>
                BaseAPI's comprehensive HTTP response helpers and status codes
            </Typography>

            <Typography>
                BaseAPI provides a complete set of HTTP response helpers that automatically set appropriate
                status codes, headers, and content formatting. These helpers make it easy to return
                consistent, standards-compliant API responses.
            </Typography>

            <Alert severity="info" sx={{ my: 3 }}>
                All response helpers automatically set correct HTTP status codes and content-type headers.
            </Alert>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Success Responses
            </Typography>

            <CodeBlock language="php" code={`<?php

class UserController extends Controller
{
    public function get(): JsonResponse
    {
        $users = User::all();
        return JsonResponse::ok($users);  // 200 OK
    }
    
    public function post(): JsonResponse
    {
        $user = new User();
        $user->save();
        return JsonResponse::created($user);  // 201 Created
    }
    
    public function delete(): JsonResponse
    {
        $user = User::find($this->id);
        $user->delete();
        return JsonResponse::noContent();  // 204 No Content
    }
}`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Error Responses
            </Typography>

            <CodeBlock language="php" code={`<?php

// 400 Bad Request
return JsonResponse::badRequest('Invalid input data');

// 401 Unauthorized
return JsonResponse::unauthorized('Authentication required');

// 403 Forbidden
return JsonResponse::forbidden('Access denied');

// 404 Not Found
return JsonResponse::notFound('Resource not found');

// 409 Conflict
return JsonResponse::conflict('Resource already exists');

// 500 Internal Server Error
return JsonResponse::error('Something went wrong', 500);`} />

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Complete Response Reference
            </Typography>

            <TableContainer component={Paper} sx={{ my: 3 }}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell><strong>Method</strong></TableCell>
                            <TableCell><strong>Status Code</strong></TableCell>
                            <TableCell><strong>Use Case</strong></TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        <TableRow>
                            <TableCell><code>JsonResponse::ok($data)</code></TableCell>
                            <TableCell>200</TableCell>
                            <TableCell>Successful GET, PUT requests</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>JsonResponse::created($data)</code></TableCell>
                            <TableCell>201</TableCell>
                            <TableCell>Successful POST requests</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>JsonResponse::noContent()</code></TableCell>
                            <TableCell>204</TableCell>
                            <TableCell>Successful DELETE requests</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>JsonResponse::badRequest($msg)</code></TableCell>
                            <TableCell>400</TableCell>
                            <TableCell>Invalid request format</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>JsonResponse::unauthorized($msg)</code></TableCell>
                            <TableCell>401</TableCell>
                            <TableCell>Authentication required</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>JsonResponse::forbidden($msg)</code></TableCell>
                            <TableCell>403</TableCell>
                            <TableCell>Access denied</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>JsonResponse::notFound($msg)</code></TableCell>
                            <TableCell>404</TableCell>
                            <TableCell>Resource not found</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>JsonResponse::conflict($msg)</code></TableCell>
                            <TableCell>409</TableCell>
                            <TableCell>Resource conflicts</TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell><code>JsonResponse::error($msg, $code)</code></TableCell>
                            <TableCell>500+</TableCell>
                            <TableCell>Server errors</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            <Typography variant="h2" gutterBottom sx={{ mt: 4 }}>
                Streaming Responses
            </Typography>

            <Typography>
                For real-time data streaming (Server-Sent Events, chunked transfer encoding), use the <code>StreamedResponse</code> class:
            </Typography>

            <CodeBlock language="php" code={`<?php

use BaseApi\\Http\\StreamedResponse;

class StreamController extends Controller
{
    public string $prompt = '';
    
    public function get(): StreamedResponse
    {
        // Server-Sent Events (SSE) streaming
        return StreamedResponse::sse(function() {
            foreach ($this->generateData() as $chunk) {
                echo "data: " . json_encode($chunk) . "\\n\\n";
                flush();
            }
        });
    }
}

// Chunked transfer encoding
return StreamedResponse::chunked(function() {
    foreach ($largeDataset as $chunk) {
        echo $chunk;
        flush();
    }
});

// Custom streaming response
return new StreamedResponse(function() {
    // Your streaming logic here
}, 200, [
    'Content-Type' => 'text/plain',
    'Cache-Control' => 'no-cache',
]);`} />

            <Alert severity="info" sx={{ my: 3 }}>
                <strong>StreamedResponse Features:</strong>
                <br />• <code>StreamedResponse::sse()</code> - Server-Sent Events with proper headers
                <br />• <code>StreamedResponse::chunked()</code> - Chunked transfer encoding
                <br />• Custom callback for streaming logic
                <br />• Automatic header management
                <br />• Perfect for AI streaming, large file downloads, and real-time data
            </Alert>

            <Alert severity="success" sx={{ mt: 4 }}>
                <strong>Best Practices:</strong>
                <br />• Use appropriate status codes for each scenario
                <br />• Include descriptive error messages
                <br />• Return consistent response formats
                <br />• Use JsonResponse helpers for automatic formatting
                <br />• Use StreamedResponse for real-time or large data transfers
            </Alert>
        </Box>
    );
}
