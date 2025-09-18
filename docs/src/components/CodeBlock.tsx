import React, { useState } from 'react';
import {
  Box,
  IconButton,
  Tooltip,
  Typography,
  Paper,
  alpha,
} from '@mui/material';
import {
  ContentCopy as CopyIcon,
  Check as CheckIcon,
} from '@mui/icons-material';

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

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(code);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy code:', err);
    }
  };

  const languageColor = languageColors[language as keyof typeof languageColors] || languageColors.default;

  const codeLines = code.split('\n');

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
      }}
    >
      {/* Header */}
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
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
          <Box
            sx={{
              width: 8,
              height: 8,
              borderRadius: '50%',
              backgroundColor: languageColor,
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
            }}
          >
            {language}
          </Typography>
          {title && (
            <>
              <Box sx={{ width: 1, height: 16, backgroundColor: 'divider' }} />
              <Typography variant="caption" color="text.secondary">
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
              '&:hover': {
                backgroundColor: alpha(languageColor, 0.1),
              },
            }}
          >
            {copied ? <CheckIcon fontSize="small" /> : <CopyIcon fontSize="small" />}
          </IconButton>
        </Tooltip>
      </Box>

      {/* Code Content */}
      <Box
        sx={{
          backgroundColor: 'grey.50',
          ...(theme => theme.palette.mode === 'dark' && {
            backgroundColor: 'grey.900',
          }),
        }}
      >
        <Box
          component="pre"
          sx={{
            m: 0,
            p: 2,
            overflow: 'auto',
            fontSize: '0.875rem',
            lineHeight: 1.6,
            fontFamily: 'SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
          }}
        >
          {showLineNumbers ? (
            <Box sx={{ display: 'flex' }}>
              <Box
                sx={{
                  pr: 2,
                  mr: 2,
                  borderRight: 1,
                  borderColor: 'divider',
                  color: 'text.disabled',
                  userSelect: 'none',
                  minWidth: 'fit-content',
                }}
              >
                {codeLines.map((_, index) => (
                  <Box key={index} component="div">
                    {index + 1}
                  </Box>
                ))}
              </Box>
              <Box sx={{ flex: 1 }}>
                <code>{code}</code>
              </Box>
            </Box>
          ) : (
            <code>{code}</code>
          )}
        </Box>
      </Box>
    </Paper>
  );
}
