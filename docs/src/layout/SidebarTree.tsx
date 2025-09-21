import React, { useState } from 'react';
import {
  List,
  ListItemButton,
  ListItemText,
  Collapse,
  Box,
  Typography,
} from '@mui/material';
import {
  ExpandLess,
  ExpandMore,
  Home,
  RocketLaunch,
  Download,
  Api,
  Folder,
  AccountTree,
  Visibility,
  Route,
  SettingsInputComponent,
  DataObject,
  SyncAlt,
  Verified,
  CloudUpload,
  Queue,
  AddTask,
  PlayArrow,
  Tune,
  Storage,
  Power,
  Settings,
  Nature,
  Cached,
  Rocket,
  Hub,
  Inventory2,
  Terminal,
  Build,
  BugReport,
  Language,
  Security,
  MenuBook,
  LibraryBooks,
  Public,
  HelpOutline,
  Error,
  Quiz,
  People,
} from '@mui/icons-material';
import { Link, useLocation } from 'react-router-dom';
import { NAV, type NavNode } from '../data/nav';

// Icon mapping for dynamic icon rendering
const iconMap = {
  home: Home,
  rocket_launch: RocketLaunch,
  download: Download,
  api: Api,
  folder: Folder,
  architecture: AccountTree,
  visibility: Visibility,
  route: Route,
  settings_input_component: SettingsInputComponent,
  schema: DataObject,
  sync_alt: SyncAlt,
  verified: Verified,
  cloud_upload: CloudUpload,
  queue: Queue,
  add_task: AddTask,
  play_arrow: PlayArrow,
  tune: Tune,
  storage: Storage,
  power: Power,
  settings: Settings,
  eco: Nature,
  cached: Cached,
  rocket: Rocket,
  hub: Hub,
  inventory_2: Inventory2,
  terminal: Terminal,
  build: Build,
  bug_report: BugReport,
  language: Language,
  security: Security,
  menu_book: MenuBook,
  library_books: LibraryBooks,
  http: Public,
  help_outline: HelpOutline,
  error: Error,
  quiz: Quiz,
  people: People,
};

// Function to render icon dynamically
function renderIcon(iconName?: string) {
  if (!iconName) return null;
  const IconComponent = iconMap[iconName as keyof typeof iconMap];
  if (!IconComponent) return null;
  return <IconComponent sx={{ mr: 1, fontSize: 18 }} />;
}

interface SidebarTreeProps {
  onItemClick?: () => void;
}

interface TreeItemProps {
  node: NavNode;
  level: number;
  onItemClick?: () => void;
}

function TreeItem({ node, level, onItemClick }: TreeItemProps) {
  const location = useLocation();
  const [expanded, setExpanded] = useState(() => {
    // Auto-expand if current path matches any child
    if (!node.children) return false;
    return node.children.some(child => 
      child.path && location.pathname.startsWith(child.path)
    );
  });

  const isActive = node.path === location.pathname;
  const hasActiveChild = node.children?.some(child => 
    child.path === location.pathname
  );

  const handleExpandClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setExpanded(!expanded);
  };

  if (node.path) {
    // Leaf node with path
    return (
      <ListItemButton
        component={Link}
        to={node.path}
        onClick={onItemClick}
        selected={isActive}
        sx={{
          pl: 2 + level * 2,
          py: 0.5,
          borderRadius: 1,
          mb: 0.25,
          '&.Mui-selected': {
            backgroundColor: 'primary.main',
            color: 'primary.contrastText',
            '&:hover': {
              backgroundColor: 'primary.dark',
            },
          },
        }}
      >
        {renderIcon(node.icon)}
        <ListItemText
          primary={node.title}
          primaryTypographyProps={{
            fontSize: '0.875rem',
            fontWeight: isActive ? 600 : 400,
          }}
        />
      </ListItemButton>
    );
  }

  // Parent node with children
  return (
    <>
      <ListItemButton
        onClick={handleExpandClick}
        sx={{
          pl: 2 + level * 2,
          py: 0.75,
          borderRadius: 1,
          mb: 0.25,
          backgroundColor: hasActiveChild ? 'action.selected' : 'transparent',
        }}
      >
        {renderIcon(node.icon)}
        <ListItemText
          primary={
            <Typography
              variant="subtitle2"
              fontWeight={600}
              color={hasActiveChild ? 'primary.main' : 'text.primary'}
              sx={{ fontSize: '0.875rem' }}
            >
              {node.title}
            </Typography>
          }
        />
        {expanded ? <ExpandLess /> : <ExpandMore />}
      </ListItemButton>
      
      <Collapse in={expanded} timeout="auto" unmountOnExit>
        <List component="div" disablePadding>
          {node.children?.map((child, index) => (
            <TreeItem
              key={`${child.title}-${index}`}
              node={child}
              level={level + 1}
              onItemClick={onItemClick}
            />
          ))}
        </List>
      </Collapse>
    </>
  );
}

export default function SidebarTree({ onItemClick }: SidebarTreeProps) {
  return (
    <Box>
      <List component="nav" disablePadding>
        {NAV.map((node, index) => (
          <TreeItem
            key={`${node.title}-${index}`}
            node={node}
            level={0}
            onItemClick={onItemClick}
          />
        ))}
      </List>
    </Box>
  );
}
