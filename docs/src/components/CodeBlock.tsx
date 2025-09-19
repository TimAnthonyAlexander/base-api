import { useState } from 'react';
import {
    Box,
    IconButton,
    Tooltip,
    Typography,
    Paper,
    alpha,
    useTheme,
    Divider,            // ← add
} from '@mui/material';
import { ContentCopy as CopyIcon, Check as CheckIcon } from '@mui/icons-material';
import { Prism as SyntaxHighlighter } from 'react-syntax-highlighter';

interface CodeBlockProps {
    language: string;
    code: string;
    title?: string;
    showLineNumbers?: boolean;
}

const languageColors = {
    php: '#777bb4',
    javascript: '#f7df1e',
    typescript: '#3178c6',
    bash: '#4eaa25',
    json: '#00d4aa',
    sql: '#e38c00',
    yaml: '#cb171e',
    xml: '#e34c26',
    css: '#1572b6',
    html: '#e34c26',
    default: '#6272a4',
};

export default function CodeBlock({
    language,
    code,
    title,
    showLineNumbers = false
}: CodeBlockProps) {
    const [copied, setCopied] = useState(false);
    const theme = useTheme();

    const handleCopy = async () => {
        await navigator.clipboard.writeText(code);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const languageColor =
        languageColors[language as keyof typeof languageColors] || languageColors.default;

    return (
        <Paper
            elevation={0}
            sx={{
                border: 1,
                borderColor: 'divider',
                borderRadius: 2,
                overflow: 'hidden',
                my: 2,
                position: 'relative',
                minWidth: 0,
                maxWidth: '100%',
            }}
        >
            <Box
                sx={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    px: 2,
                    py: 1,
                    backgroundColor: alpha(languageColor, 0.1),
                    borderBottom: 1,
                    borderColor: 'divider',
                }}
            >
                <Box
                    sx={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 1,
                        minWidth: 0,          // ← lets children shrink for ellipsis
                        flex: 1,
                    }}
                >
                    <Box
                        sx={{
                            width: 10,          // ← fixed, won’t squash
                            height: 10,
                            borderRadius: '50%',
                            backgroundColor: languageColor,
                            flex: '0 0 10px',   // ← no shrink/grow
                        }}
                    />
                    <Typography
                        variant="caption"
                        sx={{
                            fontFamily: 'monospace',
                            fontWeight: 600,
                            color: languageColor,
                            textTransform: 'uppercase',
                            letterSpacing: 1,
                            lineHeight: 1,
                            flex: '0 0 auto',
                        }}
                    >
                        {language}
                    </Typography>

                    {title && (
                        <>
                            <Divider
                                orientation="vertical"
                                flexItem
                                sx={{ mx: 1 }}
                            />
                            <Typography
                                variant="caption"
                                color="text.secondary"
                                noWrap
                                sx={{
                                    minWidth: 0,              // ← critical for ellipsis in flex
                                    flex: 1,                  // ← take remaining space
                                    overflow: 'hidden',
                                    textOverflow: 'ellipsis',
                                    whiteSpace: 'nowrap',
                                }}
                            >
                                {title}
                            </Typography>
                        </>
                    )}
                </Box>

                <Tooltip title={copied ? 'Copied!' : 'Copy code'}>
                    <IconButton
                        size="small"
                        onClick={handleCopy}
                        sx={{
                            color: languageColor,
                            flex: '0 0 auto',
                            '&:hover': { backgroundColor: alpha(languageColor, 0.1) },
                        }}
                    >
                        {copied ? <CheckIcon fontSize="small" /> : <CopyIcon fontSize="small" />}
                    </IconButton>
                </Tooltip>
            </Box>

            <Box
                sx={(theme) => ({
                    backgroundColor: theme.palette.mode === 'dark' ? '#1e1e1e' : '#f8f8f8',
                    p: 1,
                    '& pre': {
                        margin: '0 !important',
                        borderRadius: '0 !important',
                        background: `${theme.palette.mode === 'dark' ? '#1e1e1e' : '#f8f8f8'} !important`,
                        backgroundColor: `${theme.palette.mode === 'dark' ? '#1e1e1e' : '#f8f8f8'} !important`,
                    },
                    '& pre[class*="language-"]': {
                        margin: '0 !important',
                        borderRadius: '0 !important',
                        background: 'transparent !important',
                        padding: '16px !important',
                    },
                    '& *': {
                        background: 'none !important',
                        backgroundColor: 'transparent !important',
                    },
                    '& .token.comment, & .token.prolog, & .token.doctype, & .token.cdata': {
                        color: theme.palette.mode === 'dark' ? '#6272a4' : '#999999',
                    },
                    '& .token.punctuation': {
                        color: theme.palette.mode === 'dark' ? '#f8f8f2' : '#2d3748',
                    },
                    '& .token.property, & .token.tag, & .token.constant, & .token.symbol, & .token.deleted': {
                        color: theme.palette.mode === 'dark' ? '#ff79c6' : '#d73a49',
                    },
                    '& .token.boolean, & .token.number': {
                        color: theme.palette.mode === 'dark' ? '#bd93f9' : '#005cc5',
                    },
                    '& .token.selector, & .token.attr-name, & .token.string, & .token.char, & .token.builtin, & .token.inserted': {
                        color: theme.palette.mode === 'dark' ? '#50fa7b' : '#22863a',
                    },
                    '& .token.operator, & .token.entity, & .token.url, & .language-css .token.string, & .style .token.string, & .token.variable': {
                        color: theme.palette.mode === 'dark' ? '#f8f8f2' : '#2d3748',
                    },
                    '& .token.atrule, & .token.attr-value, & .token.function, & .token.class-name': {
                        color: theme.palette.mode === 'dark' ? '#f1fa8c' : '#6f42c1',
                    },
                    '& .token.keyword': {
                        color: theme.palette.mode === 'dark' ? '#8be9fd' : '#d73a49',
                    },
                    '& .token.regex, & .token.important': {
                        color: theme.palette.mode === 'dark' ? '#ffb86c' : '#e36209',
                    },
                    '& .token.important, & .token.bold': { fontWeight: 'bold' },
                    '& .token.italic': { fontStyle: 'italic' },
                    '& .token.entity': { cursor: 'help' },
                })}
            >
                <SyntaxHighlighter
                    language={language}
                    useInlineStyles={false}
                    showLineNumbers={showLineNumbers}
                    customStyle={{
                        margin: 0,
                        borderRadius: 0,
                        fontSize: '0.875rem',
                        lineHeight: 1.6,
                        fontFamily:
                            'SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
                        background: 'transparent',
                        color: theme.palette.mode === 'dark' ? '#ffffff' : '#2d3748',
                    }}
                    codeTagProps={{
                        style: {
                            fontFamily:
                                'SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
                            background: 'transparent',
                            color: theme.palette.mode === 'dark' ? '#ffffff' : '#2d3748',
                        },
                    }}
                >
                    {code}
                </SyntaxHighlighter>
            </Box>
        </Paper>
    );
}
